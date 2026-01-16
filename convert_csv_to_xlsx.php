<?php
/**
 * Conversion de CSV en XLSX via Cloudmersive API
 *
 * Instructions :
 * 1. Installez le SDK via Composer :
 *    composer require cloudmersive/cloudmersive_document_convert_api_client
 * 2. Obtenez une clé API gratuite sur https://cloudmersive.com/
 */

require_once(__DIR__ . '/vendor/autoload.php');

// Note sur le namespace :
// La documentation officielle utilise souvent Swagger\Client par défaut pour ses SDK générés.
// Si vous rencontrez une erreur "Class not found", essayez de remplacer Swagger\Client par Cloudmersive\ApiClient.

// Configuration de la clé API
$config = Swagger\Client\Configuration::getDefaultConfiguration()->setApiKey('Apikey', 'VOTRE_CLE_API_ICI');

$apiInstance = new Swagger\Client\Api\ConvertDocumentApi(
    new GuzzleHttp\Client(),
    $config
);

// Chemin vers votre fichier CSV
$input_path = 'donnees.csv';

if (!file_exists($input_path)) {
    die("Le fichier $input_path est introuvable.");
}

// L'API attend un objet SplFileObject
$input_file = new \SplFileObject($input_path);

try {
    // Conversion du fichier CSV en XLSX
    // Le résultat retourné est un SplFileObject pointant vers un fichier temporaire contenant le binaire XLSX
    $result = $apiInstance->convertDocumentCsvToXlsx($input_file);

    // Récupérer le chemin réel du fichier généré pour la lecture
    $tempFilePath = $result->getRealPath();
    $fileSize = filesize($tempFilePath);

    // Préparation du téléchargement du fichier XLSX
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="resultat.xlsx"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Envoyer le contenu binaire du fichier
    readfile($tempFilePath);
    exit;

} catch (Exception $e) {
    echo 'Erreur lors de l\'appel à l\'API : ', $e->getMessage(), PHP_EOL;
}
?>

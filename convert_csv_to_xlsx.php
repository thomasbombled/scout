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

/**
 * Note sur le namespace :
 * Selon la version du SDK installée, le namespace peut être :
 * - Swagger\Client (le plus fréquent dans la documentation)
 * - Cloudmersive\ApiClient
 */
use Swagger\Client\Configuration;
use Swagger\Client\Api\ConvertDocumentApi;
// use Cloudmersive\ApiClient\Configuration; // Alternative
// use Cloudmersive\ApiClient\Api\ConvertDocumentApi; // Alternative

// Configuration de la clé API
$config = Configuration::getDefaultConfiguration()->setApiKey('Apikey', 'VOTRE_CLE_API_ICI');

$apiInstance = new ConvertDocumentApi(
    new GuzzleHttp\Client(),
    $config
);

// Chemin vers votre fichier CSV
$input_path = 'donnees.csv';

if (!file_exists($input_path)) {
    die("Le fichier $input_path est introuvable.");
}

// L'API attend souvent un objet SplFileObject pour le téléchargement de fichiers
$input_file = new \SplFileObject($input_path);

try {
    // Conversion du fichier CSV en XLSX
    $result = $apiInstance->convertDocumentCsvToXlsx($input_file);

    /**
     * Correction de l'erreur : "Call to a member function getRealPath() on string"
     * L'API retourne directement le contenu binaire du fichier sous forme de chaîne (string).
     */
    if (is_string($result)) {
        $content = $result;
        $fileSize = strlen($content);
    } else if (is_object($result) && method_exists($result, 'getRealPath')) {
        // Au cas où une version spécifique retournerait un SplFileObject
        $content = file_get_contents($result->getRealPath());
        $fileSize = strlen($content);
    } else {
        throw new Exception("Format de réponse inconnu de l'API.");
    }

    // Préparation du téléchargement du fichier XLSX
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="resultat.xlsx"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    // Envoyer le contenu binaire
    echo $content;
    exit;

} catch (Exception $e) {
    echo 'Erreur lors de l\'appel à l\'API : ', $e->getMessage(), PHP_EOL;
}
?>

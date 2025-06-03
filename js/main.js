import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

document.addEventListener('DOMContentLoaded', () => {
  initGSAPAnimations();
  if (document.getElementById('model-canvas')) {
    initThreeJSModel();
  }
});

function initGSAPAnimations() {
  gsap.registerPlugin(ScrollTrigger);

  const sections = document.querySelectorAll('section[id]:not(#interactive-model)');
  sections.forEach(section => {
    const title = section.querySelector('h2');
    if (title) {
      gsap.fromTo(title,
        { autoAlpha: 0, y: 50 },
        {
          autoAlpha: 1, y: 0, duration: 0.8, ease: 'power2.out',
          scrollTrigger: { trigger: section, start: 'top 80%', toggleActions: 'play none none none' }
        }
      );
    }
  });

  const parallaxBg = document.querySelector('#parallax-bg');
  if (parallaxBg) {
    gsap.to(parallaxBg, {
      backgroundPosition: '50% 100%', ease: 'none',
      scrollTrigger: { trigger: '#portfolio', start: 'top bottom', end: 'bottom top', scrub: true }
    });
  }
}

// Store the ScrollTrigger instance globally or in a wider scope to manage it
let modelScrollTriggerInstance = null;

function animateModelWithScroll(modelMesh) {
  if (!modelMesh) return;

  // If a previous ScrollTrigger instance exists, kill it
  if (modelScrollTriggerInstance) {
    modelScrollTriggerInstance.kill();
    modelScrollTriggerInstance = null;
  }

  // Ensure model is reset to initial animation state if needed (GSAP might handle this)
  // For example, if you are animating from a specific rotation/scale
  modelMesh.rotation.y = 0;
  modelMesh.rotation.x = 0;
  // modelMesh.scale.set(originalScale.x, originalScale.y, originalScale.z);


  const tl = gsap.timeline();
  tl.to(modelMesh.rotation, { y: Math.PI * 2, x: Math.PI * 0.25, duration: 1 })
    .to(modelMesh.scale, { x: 1.2, y: 1.2, z: 1.2, duration: 0.5, yoyo: true, repeat: 1 }, "-=0.5"); // Scale up and back during rotation

  modelScrollTriggerInstance = ScrollTrigger.create({
    trigger: "#interactive-model",
    start: "top center",
    end: "bottom center", // Animate throughout the section's visibility in center
    scrub: 1, // Smoother scrubbing
    animation: tl,
    // markers: true, // For debugging
  });
}


function initThreeJSModel() {
  const canvas = document.getElementById('model-canvas');
  if (!canvas) { console.error('Model canvas not found!'); return; }

  let interactiveModelMesh = null; // Variable to hold the current mesh

  const scene = new THREE.Scene();
  scene.background = new THREE.Color(0xf8f9fa);

  const camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
  camera.position.set(0, 0.5, 3);

  const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
  renderer.setSize(canvas.clientWidth, canvas.clientHeight);
  renderer.setPixelRatio(window.devicePixelRatio);
  renderer.outputEncoding = THREE.sRGBEncoding;

  const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
  scene.add(ambientLight);
  const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
  directionalLight.position.set(5, 10, 7.5);
  scene.add(directionalLight);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.05;
  controls.minDistance = 1;
  controls.maxDistance = 10;

  // Fallback Cube
  const fallbackGeometry = new THREE.BoxGeometry(0.8, 0.8, 0.8); // Slightly smaller
  const fallbackMaterial = new THREE.MeshStandardMaterial({ color: 0x007bff });
  const fallbackCube = new THREE.Mesh(fallbackGeometry, fallbackMaterial);
  fallbackCube.position.y = 0.4; // Adjust if needed
  scene.add(fallbackCube);
  interactiveModelMesh = fallbackCube;
  animateModelWithScroll(interactiveModelMesh); // Animate fallback initially

  // GLTF Loader
  const loader = new GLTFLoader();
  const modelURL = 'https://threejs.org/examples/models/gltf/DamagedHelmet/glTF/DamagedHelmet.gltf';

  loader.load(modelURL, (gltf) => {
    scene.remove(fallbackCube); // Remove fallback

    const model = gltf.scene;
    const box = new THREE.Box3().setFromObject(model);
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3());
    const maxDim = Math.max(size.x, size.y, size.z);
    const scaleFactor = 1.5 / maxDim;

    model.scale.set(scaleFactor, scaleFactor, scaleFactor);
    // Recalculate center with new scale to correctly position
    const scaledBox = new THREE.Box3().setFromObject(model);
    const scaledCenter = scaledBox.getCenter(new THREE.Vector3());
    model.position.sub(scaledCenter); // Center the scaled model at origin
    // model.position.y += size.y * scaleFactor / 2; // Adjust y if pivot is at bottom

    scene.add(model);
    interactiveModelMesh = model; // Update reference

    // Re-initialize animation with the new model
    animateModelWithScroll(interactiveModelMesh);

    console.log('GLTF model loaded successfully.');
  }, undefined, (error) => {
    console.error('Error loading GLTF model:', error);
    console.log('Displaying fallback cube as model could not be loaded. Scroll animation active on fallback.');
    // Fallback is already added and animated, so no specific action needed here for animation.
  });

  function animate() {
    controls.update();
    renderer.render(scene, camera);
  }
  renderer.setAnimationLoop(animate);

  function onWindowResize() {
    const newWidth = canvas.clientWidth; // Use clientWidth of canvas for responsive sizing
    const newHeight = canvas.clientHeight;
    camera.aspect = newWidth / newHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(newWidth, newHeight);
  }

  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(onWindowResize, 100);
  });
  onWindowResize(); // Initial call
}

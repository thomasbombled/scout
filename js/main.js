import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';

let portfolioHorizontalScrollTrigger = null;
let portfolioItemsBatchST = null;
let funnyItemsBatchST = null;
let modelAppearScrollTriggerInstance = null;
let originalModelScaleY = 1;

document.addEventListener('DOMContentLoaded', () => {
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
    console.error('GSAP or ScrollTrigger not loaded!');
    return;
  }
  gsap.registerPlugin(ScrollTrigger);

  portfolioHorizontalScrollTrigger = initPortfolioHorizontalScroll();
  // initGSAPContentAnimations no longer creates batch animations for horizontal items directly
  initGSAPContentAnimations();

  if (document.getElementById('model-canvas')) {
    initThreeJSModel();
  }

  ScrollTrigger.addEventListener('refreshInit', () => {
    if (portfolioItemsBatchST) portfolioItemsBatchST.kill();
    if (funnyItemsBatchST) funnyItemsBatchST.kill();
    portfolioItemsBatchST = null; // Resetting stored instances
    funnyItemsBatchST = null;
  });

  ScrollTrigger.addEventListener('refresh', () => {
    if (portfolioHorizontalScrollTrigger && portfolioHorizontalScrollTrigger.isActive) {
      const dependentBatches = createDependentBatchAnimations(portfolioHorizontalScrollTrigger);
      portfolioItemsBatchST = dependentBatches.portfolioBatch;
      funnyItemsBatchST = dependentBatches.funnyBatch;
    } else {
        // Fallback: If horizontal scroll isn't active, maybe create them as vertical?
        // Or ensure they are not created if they strictly depend on horizontal scroll context.
        // For now, they simply won't be created if horizontalScrollTrigger is not active.
        console.log("Horizontal scroll trigger not active, dependent batches not created on refresh.");
    }
  });

  // Initial refresh to trigger the 'refresh' event and create dependent batches
  ScrollTrigger.refresh();
});

function initPortfolioHorizontalScroll() {
  const portfolioWrapper = document.querySelector('#portfolio-horizontal-wrapper');
  const portfolioSection = document.querySelector('#portfolio');
  const slides = gsap.utils.toArray("#portfolio > .portfolio-items, #portfolio > .funny-gallery");

  if (!portfolioWrapper || !portfolioSection || slides.length === 0) {
    console.warn('Portfolio horizontal scroll elements not found.');
    return null;
  }

  const horizontalTween = gsap.to(portfolioSection, {
    xPercent: -100 * (slides.length - 1),
    ease: "none",
  });

  const st = ScrollTrigger.create({
    trigger: "#portfolio-horizontal-wrapper",
    pin: true,
    scrub: 1,
    start: "top top",
    end: () => "+=" + (portfolioWrapper.offsetHeight - window.innerHeight),
    animation: horizontalTween,
    invalidateOnRefresh: true, // Main horizontal scroll needs this
  });
  return st;
}

function createDependentBatchAnimations(hScrollTrigger) {
    let stPortfolio = null;
    let stFunny = null;

    const portfolioItems = gsap.utils.toArray('.portfolio-item');
    console.log("--- Portfolio Items Batch Creation ---");
    console.log("Passed horizontalScrollTrigger (for portfolioItems):", hScrollTrigger);
    console.log("portfolioItems array:", portfolioItems);
    if (portfolioItems && portfolioItems.length > 0) { console.log("Are portfolioItems DOM elements?", portfolioItems.every(item => item instanceof Element)); } else { console.log("portfolioItems is empty or undefined"); }

    if (portfolioItems.length > 0 && hScrollTrigger) {
        stPortfolio = ScrollTrigger.batch(portfolioItems, {
            containerAnimation: hScrollTrigger,
            start: "left 90%",
            horizontal: true,
            once: true,
            onEnter: batch => gsap.fromTo(batch,
                { autoAlpha: 0, x: 50, scale: 0.95 },
                { autoAlpha: 1, x: 0, scale: 1, duration: 0.5, stagger: 0.15, ease: 'power2.out' }
            ),
            // NO invalidateOnRefresh here for batches tied to containerAnimation
        });
    }

    const portfolioItemParagraphs = gsap.utils.toArray('.portfolio-item p');
    portfolioItemParagraphs.forEach(pItem => {
        if (hScrollTrigger) {
            gsap.fromTo(pItem, {autoAlpha: 0, x: 20}, {
                autoAlpha: 1, x: 0, duration: 0.4, ease: 'power1.out',
                scrollTrigger: {
                    trigger: pItem,
                    containerAnimation: hScrollTrigger,
                    start: "left 95%",
                    horizontal: true,
                    toggleActions: 'play none none none',
                    once: true,
                    // NO invalidateOnRefresh here
                }
            });
        }
    });

    const funnyItems = gsap.utils.toArray('.funny-item');
    console.log("--- Funny Items Batch Creation ---");
    console.log("Passed horizontalScrollTrigger (for funnyItems):", hScrollTrigger);
    console.log("funnyItems array:", funnyItems);
    if (funnyItems && funnyItems.length > 0) { console.log("Are funnyItems DOM elements?", funnyItems.every(item => item instanceof Element)); } else { console.log("funnyItems is empty or undefined"); }

    if (funnyItems.length > 0 && hScrollTrigger) {
        console.log("Creating batch for funnyItems with horizontal:true AND containerAnimation");
        stFunny = ScrollTrigger.batch(funnyItems, {
            containerAnimation: hScrollTrigger,
            horizontal: true,
            start: "left 90%",
            once: true,
            onEnter: batch => {
                console.log("Batch onEnter for funnyItems (horizontal:true, containerAnimation):", batch);
                gsap.fromTo(batch, {
                    autoAlpha: 0, x: 40
                },{
                    autoAlpha: 1, x: 0, scale: 1, duration: 0.5, stagger: 0.1, ease: 'circ.out'
                });
            },
            // NO invalidateOnRefresh here
        });
    }
    return { portfolioBatch: stPortfolio, funnyBatch: stFunny };
}


function initGSAPContentAnimations() {
  // This function now only handles animations NOT dependent on horizontal scroll's refresh cycle.
  const mainTitles = gsap.utils.toArray('section[id]:not(#interactive-model):not(#portfolio) > h2, #portfolio > h2, .funny-gallery > h3');
  // Note: #portfolio > h2 and .funny-gallery > h3 are tricky.
  // If #portfolio > h2 is absolutely positioned relative to the first slide, its animation might be okay here.
  // If .funny-gallery > h3 is part of the .funny-gallery slide, its animation should ideally be in createDependentBatchAnimations
  // or triggered by the .funny-gallery slide itself. For now, keeping them here and assuming they trigger early.
  mainTitles.forEach(title => {
    gsap.fromTo(title,
      { autoAlpha: 0, y: 50 },
      {
        autoAlpha: 1, y: 0, duration: 0.8, ease: 'power2.out',
        scrollTrigger: {
          trigger: title,
          start: 'top 85%',
          toggleActions: 'play none none none',
          invalidateOnRefresh: true // These are fine as they are not container-animated
        }
      }
    );
  });

  const verticalParagraphs = gsap.utils.toArray('#about p, #experience .job-entry p, #experience .job-entry ul li, #contact .contact-details p');
  verticalParagraphs.forEach(p => {
    gsap.fromTo(p, { autoAlpha: 0, y: 30 }, {
      autoAlpha: 1, y: 0, duration: 0.6, ease: 'power1.out',
      scrollTrigger: { trigger: p, start: 'top 90%', toggleActions: 'play none none none', once: true, invalidateOnRefresh: true }
    });
  });

  const jobEntries = gsap.utils.toArray('.job-entry');
  jobEntries.forEach(entry => {
    gsap.fromTo(entry, { autoAlpha: 0, x: -60 }, {
      autoAlpha: 1, x: 0, duration: 0.7, ease: 'power2.out',
      scrollTrigger: { trigger: entry, start: 'top 85%', toggleActions: 'play none none none', once: true, invalidateOnRefresh: true }
    });
  });
}
// ... (rest of the Three.js code: setupModelAppearAnimation, initThreeJSModel remains the same) ...

function setupModelAppearAnimation(modelMesh) {
  if (!modelMesh) return;
  if (modelAppearScrollTriggerInstance) {
    modelAppearScrollTriggerInstance.kill();
    modelAppearScrollTriggerInstance = null;
  }
  originalModelScaleY = modelMesh.scale.y;
  if (originalModelScaleY === 0) originalModelScaleY = 1;
  modelMesh.scale.y = 0.01;

  const tl = gsap.timeline();
  tl.to(modelMesh.scale, { y: originalModelScaleY, duration: 1, ease: 'power2.out' });

  modelAppearScrollTriggerInstance = ScrollTrigger.create({
    trigger: "#about",
    start: "top center",
    end: "+=400",
    scrub: 1,
    animation: tl,
    once: true,
    invalidateOnRefresh: true,
  });
  console.log(`Setup "appear" animation for model (trigger: #about): ${modelMesh.uuid}, original Y scale: ${originalModelScaleY}`);
}

function initThreeJSModel() {
  const canvas = document.getElementById('model-canvas');
  if (!canvas) { console.error('Model canvas not found!'); return; }

  let interactiveModelMesh = null;

  const scene = new THREE.Scene();
  const renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(window.devicePixelRatio);
  renderer.outputEncoding = THREE.sRGBEncoding;
  renderer.setClearColor(0x1a1a1a, 1);

  const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
  camera.position.set(0, 0, 5);

  const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
  scene.add(ambientLight);
  const directionalLight = new THREE.DirectionalLight(0x16c9c3, 0.8);
  directionalLight.position.set(5, 10, 7.5);
  scene.add(directionalLight);
  const pointLight = new THREE.PointLight(0xffffff, 0.3);
  pointLight.position.set(-5, -5, 5);
  scene.add(pointLight);

  const fallbackMaterial = new THREE.MeshStandardMaterial({
    color: 0x16c9c3, transparent: true, opacity: 0.6
  });
  const fallbackGeometry = new THREE.BoxGeometry(0.5, 0.5, 0.5);
  const fallbackCube = new THREE.Mesh(fallbackGeometry, fallbackMaterial);
  fallbackCube.position.set(0, 0, 0);
  scene.add(fallbackCube);
  interactiveModelMesh = fallbackCube;
  setupModelAppearAnimation(interactiveModelMesh);

  const loader = new GLTFLoader();
  const modelURL = 'https://threejs.org/examples/models/gltf/DamagedHelmet/glTF/DamagedHelmet.gltf';

  loader.load(modelURL, (gltf) => {
    scene.remove(fallbackCube);
    const model = gltf.scene;
    const box = new THREE.Box3().setFromObject(model);
    const size = box.getSize(new THREE.Vector3());
    const maxDim = Math.max(size.x, size.y, size.z);
    const scaleFactor = 0.5 / maxDim;
    model.scale.set(scaleFactor, scaleFactor, scaleFactor);
    const finalModelScaleY = model.scale.y;
    const scaledBox = new THREE.Box3().setFromObject(model);
    const scaledCenter = scaledBox.getCenter(new THREE.Vector3());
    model.position.sub(scaledCenter);
    model.position.set(1.5, -0.5, -2);
    scene.add(model);
    interactiveModelMesh = model;
    interactiveModelMesh.scale.y = finalModelScaleY;
    setupModelAppearAnimation(interactiveModelMesh);
    console.log('GLTF model (DamagedHelmet placeholder) loaded. "Appear" animation active.');
  }, undefined, (error) => {
    console.error('Error loading GLTF model:', error);
    console.log('Displaying fallback cube. "Appear" animation active on fallback.');
  });

  function animate() {
    if (interactiveModelMesh) {
      interactiveModelMesh.rotation.y += 0.002;
      interactiveModelMesh.rotation.x += 0.0005;
    }
    renderer.render(scene, camera);
  }
  renderer.setAnimationLoop(animate);

  function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
  }

  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(onWindowResize, 100);
  });
}

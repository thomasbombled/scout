document.addEventListener('DOMContentLoaded', () => {
  // Register GSAP plugins
  gsap.registerPlugin(ScrollTrigger);

  // GSAP Animation for Section Titles
  const sections = document.querySelectorAll('section[id]');
  sections.forEach(section => {
    const title = section.querySelector('h2');
    if (title) {
      gsap.fromTo(title,
        { autoAlpha: 0, y: 50 },
        {
          autoAlpha: 1,
          y: 0,
          duration: 0.8,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: section,
            start: 'top 80%', // Trigger when section top is 80% from viewport top
            toggleActions: 'play none none none', // Play animation once when triggered
            // markers: true, // Uncomment for debugging ScrollTrigger
          }
        }
      );
    }
  });

  // Basic Parallax Effect for #parallax-bg within #portfolio
  const parallaxBg = document.querySelector('#parallax-bg');
  if (parallaxBg) {
    gsap.to(parallaxBg, {
      backgroundPosition: '50% 100%', // Adjust final position as needed, moves Y from center to 100%
      ease: 'none', // Linear movement
      scrollTrigger: {
        trigger: '#portfolio', // The section containing the parallax background
        start: 'top bottom', // When the top of #portfolio hits the bottom of the viewport
        end: 'bottom top', // When the bottom of #portfolio hits the top of the viewport
        scrub: true, // Smoothly animate with scroll
        // markers: true, // Uncomment for debugging
      }
    });
  }

  // Placeholder for Three.js initialization (if it were to be used here)
  // function initThreeJS() {
  //   // Basic Three.js setup: scene, camera, renderer
  //   // This would typically go into a more complex setup if 3D objects were being added.
  //   // For now, the subtask asks for CDN inclusion and simple GSAP/parallax.
  //   console.log('Three.js CDN is included. Further 3D integration would go here.');
  // }
  // initThreeJS();

});

/**
 * ====================================================================
 * Project: OSMANLI - ABU SEIR 1854 (عصملي) | Digital Restaurant Menu
 * Developer: Mohamad Abu Saleh
 * Phone / WhatsApp: +972598600230 (https://wa.me/972598600230)
 * Email: mohamadabusaleh83@gmail.com
 * LinkedIn: https://www.linkedin.com/in/mohamad-abu-saleh-598255350
 * ====================================================================
 */

document.addEventListener("DOMContentLoaded", () => {
  setupCategoryNav();
  setupDynamicSizeChips();
});

function setupDynamicSizeChips() {
  document.addEventListener("click", (e) => {
    const chip = e.target.closest(".size-choice-chip");
    if (!chip) return;

    const wrapper = chip.closest(".size-chips-wrapper");
    if (!wrapper) return;

    // Remove active state from siblings
    wrapper.querySelectorAll(".size-choice-chip").forEach(c => {
      c.classList.remove("active");
      c.setAttribute("aria-pressed", "false");
    });

    // Set active state for clicked chip
    chip.classList.add("active");
    chip.setAttribute("aria-pressed", "true");

    // Dynamic price display update with smooth micro-animation
    const targetId = chip.getAttribute("data-target");
    const newPrice = chip.getAttribute("data-price");
    if (targetId && newPrice) {
      const displayEl = document.getElementById(targetId);
      if (displayEl) {
        displayEl.style.transition = "transform 0.15s ease, opacity 0.15s ease";
        displayEl.style.transform = "scale(1.18)";
        displayEl.style.opacity = "0.7";
        setTimeout(() => {
          displayEl.textContent = newPrice;
          displayEl.style.transform = "scale(1)";
          displayEl.style.opacity = "1";
        }, 120);
      }
    }
  });
}

function setupCategoryNav() {
  const navLinks = document.querySelectorAll(".category-nav-link");
  const sections = document.querySelectorAll(".menu-section");

  // Smooth click scroll
  navLinks.forEach(link => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const targetId = link.getAttribute("href");
      const targetSection = document.querySelector(targetId);
      if (targetSection) {
        navLinks.forEach(l => l.classList.remove("active"));
        link.classList.add("active");
        
        const headerOffset = 130;
        const elementPosition = targetSection.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

        window.scrollTo({
          top: offsetPosition,
          behavior: "smooth"
        });
      }
    });
  });

  // ScrollSpy to highlight active category on scroll
  window.addEventListener("scroll", () => {
    let current = "";
    const scrollY = window.pageYOffset;

    sections.forEach(section => {
      const sectionTop = section.offsetTop - 160;
      const sectionHeight = section.offsetHeight;
      if (scrollY >= sectionTop && scrollY < sectionTop + sectionHeight) {
        current = section.getAttribute("id");
      }
    });

    if (current) {
      navLinks.forEach(link => {
        link.classList.remove("active");
        if (link.getAttribute("href") === `#${current}`) {
          link.classList.add("active");
        }
      });
    }
  }, { passive: true });
}

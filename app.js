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
  setupShareMenu();
  setupFeedbackSystem();
});

// ========================================================
// 1. إدارة الأحجام والأسعار الديناميكية (Dynamic Size Chips)
// ========================================================
function setupDynamicSizeChips() {
  document.addEventListener("click", (e) => {
    const chip = e.target.closest(".size-choice-chip");
    if (!chip) return;

    const wrapper = chip.closest(".size-chips-wrapper");
    if (!wrapper) return;

    // إزالة التحديد عن باقي الأحجام
    wrapper.querySelectorAll(".size-choice-chip").forEach(c => {
      c.classList.remove("active");
      c.setAttribute("aria-pressed", "false");
    });

    // تفعيل الحجم المختار
    chip.classList.add("active");
    chip.setAttribute("aria-pressed", "true");

    // تحديث السعر المعروض بحركة أنيقة
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

// ========================================================
// 2. شريط التنقل السريع بين الأقسام (Sticky Category Nav & ScrollSpy)
// ========================================================
function setupCategoryNav() {
  const navLinks = document.querySelectorAll(".category-nav-link");
  const sections = document.querySelectorAll(".menu-section");

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

// ========================================================
// 3. زر مشاركة المنيو السريع (Quick Share Menu Button)
// ========================================================
function setupShareMenu() {
  const shareBtn = document.getElementById("btnShareMenu");
  if (!shareBtn) return;

  shareBtn.addEventListener("click", async () => {
    const shareUrl = window.location.href.split("#")[0];
    const shareData = {
      title: "عصملي - OSMANLI (ABU SEIR 1854)",
      text: "تصفح منيو حلويات عصملي الفاخرة (الكنافة والحلويات الشامية والتراثية) واختر ما يناسبك:",
      url: shareUrl
    };

    // تجربة مشاركة الهاتف الأصلية (Native Web Share API)
    if (navigator.share) {
      try {
        await navigator.share(shareData);
        return;
      } catch (err) {
        // إذا قام المستخدم بالإلغاء لا نفعل شيئاً، وإذا حدث خطأ نعتمد النسخ للحافظة
        if (err.name === "AbortError") {
          return;
        }
      }
    }

    // البديل: النسخ إلى الحافظة تلقائياً (Clipboard Copy Fallback)
    copyShareLinkToClipboard(shareUrl);
  });
}

function copyShareLinkToClipboard(url) {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url)
      .then(() => {
        showToast("تم نسخ رابط المنيو بنجاح! 🔗 شاركه الآن مع أحبابك", "success");
      })
      .catch(() => {
        fallbackCopyExec(url);
      });
  } else {
    fallbackCopyExec(url);
  }
}

function fallbackCopyExec(url) {
  const dummy = document.createElement("input");
  dummy.value = url;
  document.body.appendChild(dummy);
  dummy.select();
  try {
    document.execCommand("copy");
    showToast("تم نسخ رابط المنيو بنجاح! 🔗", "success");
  } catch (e) {
    showToast("رابط المنيو: " + url, "info");
  }
  document.body.removeChild(dummy);
}

// ========================================================
// 4. نظام تقييم وملاحظات الزبائن (Private Feedback System)
// ========================================================
function setupFeedbackSystem() {
  const starBtns = document.querySelectorAll("#starRatingSelector .star-btn");
  const ratingInput = document.getElementById("selectedRating");
  const ratingHint = document.getElementById("ratingTextHint");
  const hintMap = {
    1: "بحاجة لتحسين (1 من 5)",
    2: "مقبول (2 من 5)",
    3: "جيد (3 من 5)",
    4: "جيد جداً (4 من 5)",
    5: "ممتاز (5 من 5) ⭐"
  };

  starBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      const val = parseInt(btn.getAttribute("data-rating"), 10);
      if (ratingInput) ratingInput.value = val;
      if (ratingHint) ratingHint.textContent = hintMap[val] || `${val} من 5`;

      starBtns.forEach(b => {
        const bVal = parseInt(b.getAttribute("data-rating"), 10);
        if (bVal <= val) {
          b.classList.add("active");
        } else {
          b.classList.remove("active");
        }
      });
    });
  });
}

function openFeedbackModal() {
  const modal = document.getElementById("feedbackModalOverlay");
  if (modal) {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }
}

function closeFeedbackModal() {
  const modal = document.getElementById("feedbackModalOverlay");
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "";
  }
}

async function submitFeedbackForm(event) {
  event.preventDefault();
  const form = document.getElementById("feedbackForm");
  const rating = document.getElementById("selectedRating") ? document.getElementById("selectedRating").value : 5;
  const comment = document.getElementById("feedbackComment") ? document.getElementById("feedbackComment").value.trim() : "";
  const customerName = document.getElementById("feedbackCustomerName") ? document.getElementById("feedbackCustomerName").value.trim() : "";
  const submitBtn = document.getElementById("btnSubmitFeedback");
  const statusMsg = document.getElementById("feedbackStatusMsg");

  if (!comment) {
    alert("يرجى كتابة رأيك أو ملاحظتك.");
    return;
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>جارٍ الإرسال...</span>';
  }

  try {
    const res = await fetch("feedback.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        rating: parseInt(rating, 10),
        comment: comment,
        customer_name: customerName
      })
    });
    const data = await res.json();
    if (data.success) {
      form.reset();
      closeFeedbackModal();
      showToast(data.message || "شكراً لك! رأيك يهمنا ويسعدنا دائماً في عصملي ❤️", "success");
    } else {
      if (statusMsg) {
        statusMsg.style.display = "block";
        statusMsg.className = "feedback-status-msg error";
        statusMsg.textContent = data.error || "حدث خطأ أثناء حفظ التقييم.";
      }
    }
  } catch (err) {
    // في حال عدم توفر اتصال بالخادم المباشر أو الاستضافة الثابتة
    form.reset();
    closeFeedbackModal();
    showToast("شكراً جزيلاً لك! تم استلام تقييمك ورأيك يهمنا ويسعدنا دائماً في عصملي ❤️", "success");
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<span>إرسال الرأي</span> <i class="fa-solid fa-paper-plane"></i>';
    }
  }
}

// ========================================================
// 5. التنبيه العائم السريع (Toast Notification Utility)
// ========================================================
let toastTimer = null;
function showToast(msg, type = "success") {
  const toast = document.getElementById("toastNotification");
  const toastMsg = document.getElementById("toastMsg");
  const toastIcon = document.getElementById("toastIcon");

  if (!toast || !toastMsg) {
    alert(msg);
    return;
  }

  toastMsg.textContent = msg;

  if (toastIcon) {
    if (type === "info") {
      toastIcon.className = "fa-solid fa-circle-info";
    } else if (type === "error") {
      toastIcon.className = "fa-solid fa-circle-exclamation";
    } else {
      toastIcon.className = "fa-solid fa-circle-check";
    }
  }

  toast.style.display = "flex";
  toast.classList.remove("fade-out");
  toast.classList.add("show");

  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove("show");
    toast.classList.add("fade-out");
    setTimeout(() => {
      toast.style.display = "none";
    }, 300);
  }, 3500);
}

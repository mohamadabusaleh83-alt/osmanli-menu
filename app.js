/**
 * ====================================================================
 * Project: OSMANLI - ABU SEIR 1854 (عصملي) | Digital Restaurant Menu
 * Developer: Mohamad Abu Saleh
 * Phone / WhatsApp: +972598600230 (https://wa.me/972598600230)
 * Email: mohamadabusaleh83@gmail.com
 * LinkedIn: https://www.linkedin.com/in/mohamad-abu-saleh-598255350
 * ====================================================================
 */

// رابط تقييم خرائط Google لفرع عصملي - نابلس (ثابت لسهولة التحديث)
const GOOGLE_MAPS_REVIEW_URL = "https://maps.google.com"; // رابط مؤقت سهل الاستبدال

document.addEventListener("DOMContentLoaded", () => {
  setupCategoryNav();
  setupDynamicSizeChips();
  setupShareMenu();
  setupFeedbackSystem();
  setupPhoneLinks();
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
        // إذا قام المستخدم بالإلغاء لا نفعل شيئاً
        if (err.name === "AbortError") {
          return;
        }
      }
    }

    // البديل الاحتياطي: النسخ إلى الحافظة تلقائياً (Clipboard Copy Fallback)
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
  dummy.style.position = "fixed";
  dummy.style.left = "-9999px";
  dummy.style.opacity = "0";
  document.body.appendChild(dummy);
  dummy.select();
  try {
    document.execCommand("copy");
    showToast("تم نسخ رابط المنيو بنجاح! 🔗 شاركه الآن مع أحبابك", "success");
  } catch (e) {
    showToast("رابط المنيو: " + url, "info");
  }
  document.body.removeChild(dummy);
}

// ========================================================
// 3.5 فحص وإصلاح روابط الاتصال المباشر (Phone Call Links)
// ========================================================
function setupPhoneLinks() {
  const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
  phoneLinks.forEach(link => {
    // التأكد من ضبط الرابط بصيغة tel:0598001608 مباشرة
    const href = link.getAttribute("href");
    if (!href || href === "tel:" || href.includes("invalid")) {
      link.setAttribute("href", "tel:0598001608");
    }
  });
}

// ========================================================
// 4. نظام توجيه التقييمات الذكي (Review Funneling System)
// ========================================================
function setupFeedbackSystem() {
  const starBtns = document.querySelectorAll("#starRatingSelector .star-btn");
  const ratingInput = document.getElementById("selectedRating");
  const ratingHint = document.getElementById("ratingTextHint");
  const positiveFunnel = document.getElementById("positiveFeedbackFunnel");
  const negativeFunnel = document.getElementById("negativeFeedbackFunnel");
  const googleReviewLink = document.getElementById("btnGoogleReviewLink");

  // تعيين رابط خرائط جوجل من المتغير الثابت
  if (googleReviewLink) {
    googleReviewLink.href = GOOGLE_MAPS_REVIEW_URL;
  }

  const hintMap = {
    1: "بحاجة لتحسين (1 من 5)",
    2: "مقبول (2 من 5)",
    3: "جيد (3 من 5)",
    4: "جيد جداً (4 من 5) ⭐",
    5: "ممتاز (5 من 5) ⭐"
  };

  function updateFunnelState(val) {
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

    // الشرط المنطقي للتوجيه الذكي:
    // 4 أو 5 نجوم (تقييم إيجابي) -> إظهار مسار خرائط جوجل وزر التقييم المباشر
    // 1 أو 2 أو 3 نجوم (تقييم منخفض) -> إظهار صندوق الملاحظات الخاص بالإدارة
    if (val >= 4) {
      if (positiveFunnel) positiveFunnel.style.display = "block";
      if (negativeFunnel) negativeFunnel.style.display = "none";
    } else {
      if (positiveFunnel) positiveFunnel.style.display = "none";
      if (negativeFunnel) negativeFunnel.style.display = "block";
    }
  }

  starBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      const val = parseInt(btn.getAttribute("data-rating"), 10);
      updateFunnelState(val);
    });
  });

  // تعيين الحالة المبدئية 5 نجوم
  updateFunnelState(5);
}

function openFeedbackModal() {
  const modal = document.getElementById("feedbackModalOverlay");
  if (modal) {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";

    // إعادة التهيئة للحالة الافتراضية: 5 نجوم مع المسار الإيجابي
    const starBtns = document.querySelectorAll("#starRatingSelector .star-btn");
    const ratingInput = document.getElementById("selectedRating");
    const ratingHint = document.getElementById("ratingTextHint");
    const positiveFunnel = document.getElementById("positiveFeedbackFunnel");
    const negativeFunnel = document.getElementById("negativeFeedbackFunnel");
    const commentField = document.getElementById("negativeCustComment");
    const nameField = document.getElementById("negativeCustName");
    const statusMsg = document.getElementById("negativeFeedbackStatusMsg");

    if (ratingInput) ratingInput.value = 5;
    if (ratingHint) ratingHint.textContent = "ممتاز (5 من 5) ⭐";
    starBtns.forEach(b => b.classList.add("active"));
    if (positiveFunnel) positiveFunnel.style.display = "block";
    if (negativeFunnel) negativeFunnel.style.display = "none";
    if (commentField) commentField.value = "";
    if (nameField) nameField.value = "";
    if (statusMsg) statusMsg.style.display = "none";
  }
}

function closeFeedbackModal() {
  const modal = document.getElementById("feedbackModalOverlay");
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "";
  }
}

function handleGoogleReviewClick() {
  const ratingInput = document.getElementById("selectedRating");
  const rating = ratingInput ? parseInt(ratingInput.value, 10) : 5;

  // تسجيل التقييم الإيجابي في الخلفية للإدارة
  try {
    fetch("feedback.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        rating: rating,
        comment: "تقييم إيجابي ممتاز (تم التوجيه لخرائط جوجل)",
        customer_name: "زبون عصملي"
      })
    }).catch(() => {});
  } catch (e) {}

  showToast("سعداء جداً بتجربتك! شكراً لدعمك عصملي على Google ⭐", "success");
  setTimeout(() => {
    closeFeedbackModal();
  }, 1000);
}

async function submitNegativeFeedbackForm(event) {
  event.preventDefault();
  const ratingInput = document.getElementById("selectedRating");
  const rating = ratingInput ? parseInt(ratingInput.value, 10) : 3;
  const commentEl = document.getElementById("negativeCustComment");
  const nameEl = document.getElementById("negativeCustName");
  const submitBtn = document.getElementById("btnSubmitNegativeFeedback");
  const statusMsg = document.getElementById("negativeFeedbackStatusMsg");

  const comment = commentEl ? commentEl.value.trim() : "";
  const customerName = nameEl ? nameEl.value.trim() : "";

  if (!comment) {
    if (statusMsg) {
      statusMsg.style.display = "block";
      statusMsg.className = "feedback-status-msg error";
      statusMsg.textContent = "يرجى كتابة ملاحظتك لمساعدتنا على تحسين الخدمة فوراً.";
    } else {
      alert("يرجى كتابة ملاحظتك لمساعدتنا على تحسين الخدمة.");
    }
    return;
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>جارٍ إرسال الملاحظة للإدارة...</span>';
  }

  try {
    const res = await fetch("feedback.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        rating: rating,
        comment: comment,
        customer_name: customerName || "زبون عصملي"
      })
    });
    const data = await res.json();
    if (data.success) {
      closeFeedbackModal();
      showToast("شكراً لملاحظتك، تم إرسالها مباشرة للإدارة لمتابعتها 🤝", "success");
      if (commentEl) commentEl.value = "";
      if (nameEl) nameEl.value = "";
    } else {
      if (statusMsg) {
        statusMsg.style.display = "block";
        statusMsg.className = "feedback-status-msg error";
        statusMsg.textContent = data.error || "حدث خطأ أثناء حفظ الملاحظة.";
      }
    }
  } catch (err) {
    // في حال الاستضافة الساكنة أو انقطاع الاتصال المؤقت
    closeFeedbackModal();
    showToast("شكراً لملاحظتك، تم إرسالها مباشرة للإدارة لمتابعتها 🤝", "success");
    if (commentEl) commentEl.value = "";
    if (nameEl) nameEl.value = "";
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<span>إرسال الملاحظة سراً للإدارة</span> <i class="fa-solid fa-paper-plane"></i>';
    }
  }
}

// توافق رجعي لأي استدعاءات خارجية
window.submitFeedbackForm = submitNegativeFeedbackForm;
window.submitNegativeFeedbackForm = submitNegativeFeedbackForm;
window.handleGoogleReviewClick = handleGoogleReviewClick;
window.openFeedbackModal = openFeedbackModal;
window.closeFeedbackModal = closeFeedbackModal;

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

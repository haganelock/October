/* Hagane static site — shared behavior: i18n (IT default in DOM, EN from dict),
   header light/dark auto-theme, mobile menu, safety fallbacks. */
(function () {
  "use strict";

  /* ---------- Silence benign third-party theme-init noise ----------
     vendor.js is the original "Arts" theme monolith; in this static
     extraction some of its auto-init routines run against DOM nodes that
     no longer exist (WebGL canvas, custom cursor, barba containers) and
     throw harmless TypeErrors. The page renders fine via our own scripts,
     so we swallow errors originating from vendor.js (and the ResizeObserver
     loop notice) to keep the console clean. Our own errors still surface. */
  window.addEventListener("error", function (e) {
    if (e && e.message && e.message.indexOf("ResizeObserver loop") !== -1) {
      e.stopImmediatePropagation();
      return;
    }
    if (e && e.filename && e.filename.indexOf("vendor.js") !== -1) {
      e.stopImmediatePropagation();
      if (e.preventDefault) e.preventDefault();
    }
  }, true);

  /* ---------- Safety: hide preloader even if the theme framework fails ---------- */
  function hidePreloader() {
    var p = document.getElementById("js-preloader");
    if (p) { p.style.transition = "opacity .6s"; p.style.opacity = "0"; setTimeout(function () { p.style.display = "none"; }, 700); }
  }
  window.addEventListener("load", function () { setTimeout(hidePreloader, 2500); });
  setTimeout(hidePreloader, 6000);

  /* ---------- Safety: lazy images / backgrounds the framework didn't resolve ---------- */
  function resolveLazy() {
    document.querySelectorAll("img[data-src]").forEach(function (img) {
      var cur = img.getAttribute("src");
      if (!cur || cur === "#" || cur === "") img.src = img.getAttribute("data-src");
    });
    document.querySelectorAll(".lazy-bg[data-src]").forEach(function (el) {
      if (!el.style.backgroundImage) el.style.backgroundImage = "url('" + el.getAttribute("data-src") + "')";
    });
  }
  window.addEventListener("load", function () { setTimeout(resolveLazy, 3000); });

  /* ---------- Mobile overlay menu (fallback toggle) ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    var burger = document.getElementById("js-burger");
    if (burger) {
      burger.addEventListener("click", function () {
        document.body.classList.toggle("menu-opened");
        document.body.classList.toggle("overlay-menu-opened");
      });
    }
  });

  /* ---------- Accordion fallback (only if Bootstrap's collapse isn't present) ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    var hasBootstrap = window.jQuery && window.jQuery.fn && window.jQuery.fn.collapse;
    if (hasBootstrap) return;
    document.querySelectorAll('[data-toggle="collapse"]').forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        var sel = btn.getAttribute("data-target") || btn.getAttribute("href");
        var target = sel && document.querySelector(sel);
        if (!target) return;
        var parentSel = target.getAttribute("data-parent");
        if (parentSel) {
          document.querySelectorAll(parentSel + " .collapse.show").forEach(function (c) {
            if (c !== target) { c.classList.remove("show"); }
          });
        }
        target.classList.toggle("show");
        btn.classList.toggle("collapsed", !target.classList.contains("show"));
      });
    });
  });

  /* ---------- Header light/dark auto-theme ----------
     The fixed header carries dark text by default; over any dark block
     (footer, dark CTA, dark sections) it must switch to white. We probe
     what sits behind the header and toggle the `.light` class accordingly.
     This replaces the old per-page inline logic and fixes the menu being
     invisible at the bottom of the page over the dark footer. */
  function initHeaderTheme() {
    var header = document.getElementById("page-header");
    if (!header) return;
    /* New coherent site uses a fixed always-light header (.hgx-header); skip the
       luminance auto-theme entirely. */
    if (header.classList.contains("hgx-header")) return;
    // Sample the ACTUAL rendered background colour just under the header.
    // This is robust to GSAP-pinned/transformed sections (whose bounding
    // boxes lie about their on-screen position) — we read what is really
    // painted behind the menu and pick white text only over dark backgrounds.
    function luminanceAt(x, y) {
      var el = document.elementFromPoint(x, y);
      var guard = 0;
      while (el && guard++ < 12) {
        if (el === header || header.contains(el)) { el = el.parentElement; continue; }
        var bg = window.getComputedStyle(el).backgroundColor;
        var m = bg && bg.match(/rgba?\(([^)]+)\)/);
        if (m) {
          var p = m[1].split(",").map(function (s) { return parseFloat(s); });
          var a = p[3] === undefined ? 1 : p[3];
          if (a > 0.15) return 0.299 * p[0] + 0.587 * p[1] + 0.114 * p[2];
        }
        var bgi = el.style && el.style.backgroundImage;
        el = el.parentElement;
      }
      return 255; // default: assume light background
    }
    function update() {
      var hb = header.getBoundingClientRect();
      var y = Math.max(4, hb.bottom - 6);
      var w = window.innerWidth || document.documentElement.clientWidth;
      var xs = [Math.round(w * 0.28), Math.round(w * 0.5), Math.round(w * 0.72)];
      var sum = 0, n = 0;
      for (var i = 0; i < xs.length; i++) {
        var lum = luminanceAt(xs[i], y);
        if (lum != null) { sum += lum; n++; }
      }
      var avg = n ? sum / n : 255;
      header.classList.toggle("light", avg < 135);
    }
    var ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () { update(); ticking = false; });
    }
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    window.addEventListener("load", function () { setTimeout(update, 300); });
    setTimeout(update, 400);
    setInterval(update, 800); // catch GSAP-pinned transforms that don't fire scroll
  }
  if (document.readyState !== "loading") initHeaderTheme();
  else document.addEventListener("DOMContentLoaded", initHeaderTheme);

  /* ---------- Animated 3-phase hero ---------- */
  function initHero() {
    var hero = document.querySelector(".hero-anim");
    if (!hero) return;
    var dots = [].slice.call(hero.querySelectorAll(".hero-dot"));
    var phase = 1;
    var timer = null;
    function show(p) {
      phase = p;
      hero.setAttribute("data-phase", String(p));
      dots.forEach(function (d, i) { d.classList.toggle("is-active", i === p - 1); });
    }
    function next() { show(phase % 3 + 1); }
    function start() { stop(); timer = setInterval(next, 3200); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    dots.forEach(function (d, i) {
      d.addEventListener("click", function () { show(i + 1); start(); });
    });
    show(1);
    start();
  }
  if (document.readyState !== "loading") initHero();
  else document.addEventListener("DOMContentLoaded", initHero);

  /* ---------- Lightbox for zoomable images ---------- */
  function initLightbox() {
    var imgs = [].slice.call(document.querySelectorAll("img.zoomable"));
    if (!imgs.length) return;
    var box = document.createElement("div");
    box.className = "lightbox";
    box.innerHTML = '<button class="lightbox__close" aria-label="Chiudi">&times;</button><img alt="">';
    document.body.appendChild(box);
    var big = box.querySelector("img");
    function open(src, alt) {
      big.src = src;
      big.alt = alt || "";
      box.classList.add("open");
      document.body.style.overflow = "hidden";
    }
    function close() {
      box.classList.remove("open");
      document.body.style.overflow = "";
    }
    imgs.forEach(function (im) {
      im.addEventListener("click", function () { open(im.currentSrc || im.src, im.alt); });
    });
    box.addEventListener("click", function (e) {
      if (e.target === big) return;
      close();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && box.classList.contains("open")) close();
    });
  }
  if (document.readyState !== "loading") initLightbox();
  else document.addEventListener("DOMContentLoaded", initLightbox);

  /* ====================================================================
     i18n — Italian is the DOM default (captured on load); the dictionary
     only stores the English strings, keyed by data-i18n / data-i18n-html.
     ==================================================================== */
  var DICT = {
    /* nav + mobile menu */
    nav_overview: { en: "Overview" }, nav_specs: { en: "Specs" }, nav_faq: { en: "F.A.Q." }, nav_buy: { en: "Buy now" },
    menu_overview: { en: "Overview" }, menu_features: { en: "Features" }, menu_storyboard: { en: "Storyboard" }, menu_app: { en: "App" }, menu_showcase: { en: "Showcase" },

    /* hero */
    hero_keyless: { en: "Keyless" }, hero_bikelock: { en: "Bike Lock" },
    hero_preorder: { en: "Preorder now" }, hero_save: { en: "Save 50%" },
    hero_msg: { en: "Keyless. Hands-free app." },

    /* homepage (rebuilt) */
    hp_hero_eyebrow: { en: "Made in Italy" },
    hp_hero_h1: { en: "The <span class=\"red\">keyless</span> bike lock" },
    hp_hero_sub: { en: "18 mm of hardened steel with no keyhole to force. Unlock it with the app in your pocket." },
    hp_cta_buy: { en: "Preorder now" },
    hp_cta_save: { en: "Save 50%" },
    hp_cta_how: { en: "How it works" },
    hp_how_title: { en: "How it works" },
    hp_feat_title: { en: "Why Hagane" },
    hp_feat_sub: { en: "No keys, no keyhole to force. Just steel and an app." },
    hp_uses_title: { en: "For every set of two wheels" },
    hp_uses_sub: { en: "Bikes, scooters, kick scooters and motorbikes: the same steel strength." },
    hp_models_title: { en: "Choose your Hagane" },
    hp_models_sub: { en: "Two versions, the same keyless technology." },
    hp_m1_tag: { en: "For your bike" },
    hp_m2_tag: { en: "For scooters & motorbikes" },
    hp_cta_title: { en: "Keep your two wheels safe." },

    /* specs comparison */
    cmp_for: { en: "Ideal for" },
    cmp_thickness: { en: "Bar thickness" },
    cmp_weight: { en: "Weight" },
    cmp_material: { en: "Material" },
    cmp_unlock: { en: "Unlocking" },
    cmp_conn: { en: "Connectivity" },
    cmp_battery: { en: "Battery" },
    val_steel: { en: "High-strength hardened steel" },
    val_keyless: { en: "Keyless, from the app — no keys" },
    val_ble: { en: "Bluetooth LE (iOS & Android)" },
    val_battery: { en: "Not rechargeable · ~10 years at rest, 2 years at 20 unlocks/day" },

    /* app demo (3 steps) */
    app_title: { en: "Keyless, hands-free" },
    app_sub: { en: "With Bluetooth and hands-free mode on, you control Hagane without even taking your phone out of your pocket." },
    app_s1_h: { en: "Register the lock" },
    app_s1_p: { en: "Register Hagane once with the app, then keep your phone in your pocket." },
    app_s2_h: { en: "Press the button" },
    app_s2_p: { en: "Step up to your bike and press the black button on the lock." },
    app_s3_h: { en: "Unlock and go" },
    app_s3_p: { en: "Hagane recognises your phone, it opens and you're off." },
    hero_off: { en: "Screen locked" },

    /* full-screen claims */
    claim1: { en: "They carry you <span class=\"color-dark-3\">everywhere</span>. They're <span class=\"color-dark-3\">loyal</span>. <br>And we know how much you love them. <br>Losing your two wheels is a trauma. <br>Spare yourself with <span class=\"color-dark-3\">Hagane</span>." },
    claim2: { en: "Tough. Keyless. Smart. <br><span class=\"color-dark-3\">Forever.</span>" },
    claim3: { en: "Everything you need <br>is already in your <span class=\"color-dark-3\">phone</span>" },
    claim4: { en: "If it has <span class=\"color-dark-3\">two wheels</span>, <br>it needs <span class=\"color-dark-3\">Hagane</span>." },

    /* shared CTA / links */
    link_specs: { en: "Discover the technical specs" },
    btn_preorder: { en: "PREORDER NOW – SAVE 50%" },
    cta_title: { en: "Keep your wheels safe" },
    cta_buy: { en: "Buy your Hagane <small>PREORDER NOW SAVE 50%</small>" },
    cta_faq: { en: "Want to know more? Read the F.A.Q." },

    /* features slider */
    sl1_h: { en: "The lock" },
    sl1_p: { en: "Hagane is the first keyless bike, scooter and motorbike lock made in Italy. But don't mistake it for just another smart gadget. Above all, Hagane is the strength of 18 mm of steel." },
    sl2_h: { en: "Steel" },
    sl2_p: { en: "Hagane is the first keyless bike, scooter and motorbike lock made in Italy. But don't mistake it for just another smart gadget. Above all, Hagane is the strength of 18 mm of steel." },
    sl3_h: { en: "The Hagane system" },
    sl3_p: { en: "Hagane is the first keyless bike, scooter and motorbike lock made in Italy. But don't mistake it for just another smart gadget. Above all, Hagane is the strength of 18 mm of steel." },
    f_robusto_h: { en: "Tough" },
    f_robusto_p: { en: "It's in our name. To the Japanese, Hagane means steel. To us, it's the guarantee of having chosen the most solid of materials." },
    f_smart_h: { en: "Smart" },
    f_smart_p: { en: "Hagane, its app, your smartphone. To protect your bike you need nothing else." },
    f_keyless_h: { en: "Keyless" },
    f_keyless_p: { en: "Want to keep prowlers away from your bike? No key and no keyhole: done." },
    f_weather_h: { en: "Weatherproof" },
    f_weather_p: { en: "Rain, sun, salt air: Hagane is built to stay outdoors all year round without rusting or jamming." },
    f_shock_h: { en: "Shockproof" },
    f_shock_p: { en: "The steel body absorbs blows and break-in attempts and keeps working perfectly." },
    f_reflect_h: { en: "Reflective sticker" },
    f_reflect_p: { en: "A small detail that makes you more visible on the road, even at night." },
    f_button_h: { en: "Unlock button" },
    f_button_p: { en: "Once unlocked, all it takes is a finger. Nothing else. Really. No need to read on." },
    f_connected_h: { en: "Always connected" },
    f_connected_p: { en: "Hagane is always connected: just bring your phone close to unlock it. Spoiler: you don't even need it in your hand." },
    f_ble_h: { en: "Bluetooth LE connection" },
    f_ble_p: { en: "If you're thinking \"it'll drain my phone\", we beat you to it: it won't. It's low energy, baby." },

    /* storyboard captions */
    sb1: { en: "Your bike, right where you left it. Hagane watches over it." },
    sb2: { en: "Step closer. Hagane recognises your phone and unlocks." },
    sb3: { en: "One single move: press the button and the lock opens." },
    sb4: { en: "18 mm of hardened steel. No keyhole to force." },
    sb5: { en: "Share access with whoever you like, straight from the app." },
    sb6: { en: "Get a notification if someone tries to tamper with it." },
    sb7: { en: "Long-lasting battery: months of use on a single charge." },
    sb8: { en: "Bluetooth LE: a stable connection that won't drain your phone." },
    sb9: { en: "Made for bikes, scooters, motorbikes and kick scooters." },
    sb10: { en: "Essential design, conceived and made in Italy." },
    sb11: { en: "Keep your two wheels safe. Forever." },
    sb12: { en: "Hagane. Keyless bike lock." },

    /* app slider */
    app1_h: { en: "Register your lock" },
    app1_p: { en: "Burglars should thank us: with Hagane they can retire. To operate the lock you need just one thing — your phone. We know you never leave it behind: problem solved." },
    app2_h: { en: "Lock and unlock in one gesture" },
    app2_p: { en: "Bring your phone close and press: Hagane unlocks. No key to find, no combination to remember. Everything happens through the app, securely and instantly." },
    app3_h: { en: "Control everything from the app" },
    app3_p: { en: "Battery status, unlock history, access sharing and security alerts: manage every aspect of your Hagane from a single screen." },

    /* showcase */
    sc1_h: { en: "City Bike" }, sc1_p: { en: "For those who ride through the city every day and don't want to worry about it anymore." },
    sc2_h: { en: "Scooter" }, sc2_p: { en: "The same steel strength, designed for your urban commute." },
    sc3_h: { en: "Kick scooters" }, sc3_p: { en: "Light to carry, impossible to force: ideal for your kick scooter." },
    sc4_h: { en: "Motorcycles" }, sc4_p: { en: "Serious protection for your motorbike, with no bulk and no keys." },

    /* footer */
    footer_contacts: { en: "Contacts" },
    footer_company: { en: "Company details" },
    footer_capital: { en: "Share capital € 10,000 fully paid" },
    footer_startup: { en: "Innovative start-up" },
    footer_rights: { en: "© 2026 HGN S.r.l. — All rights reserved." },
    footer_privacy: { en: "Privacy" }, footer_cookie: { en: "Cookie" }, footer_terms: { en: "Terms of sale" },

    /* specs page */
    specs_title1: { en: "How it's made" },
    specs_select: { en: "Select a model" },
    specs_h_tech: { en: "Technical specs" },
    specs_h_app: { en: "App requirements" },
    specs_h_box: { en: "What's in the box" },
    spec_dim: { en: "Size & weight" },
    spec_dim_b: { en: "Steel body 18 mm thick. Compact size and weight for everyday carry. Placeholder text to be replaced with the final content before publication." },
    spec_mat: { en: "Material" },
    spec_mat_b: { en: "High-strength hardened steel, treated against corrosion and the elements. Placeholder text to be replaced with the final content before publication." },
    spec_conn: { en: "Connectivity" },
    spec_conn_b: { en: "Bluetooth Low Energy (BLE) to pair with the Hagane app on iOS and Android. Placeholder text to be replaced with the final content before publication." },
    spec_batt: { en: "Battery" },
    spec_batt_b: { en: "Long-lasting rechargeable battery with several months of autonomy per charge cycle. Placeholder text to be replaced with the final content before publication." },
    spec_col: { en: "Colours" },
    spec_col_b: { en: "Available in several finishes. Placeholder text to be replaced with the final content before publication." },
    specs_app_p1: { en: "All you need is the smartphone already in your pocket: Hagane works with iPhones and with common Android phones running iOS 15+ or Android 12+." },
    specs_app_check: { en: "How do I check?" },
    specs_app_ios: { en: "<strong style=\"color:var(--ink)\">iPhone:</strong> Settings → General → About → look for “Software Version” (you need 15 or higher)." },
    specs_app_android: { en: "<strong style=\"color:var(--ink)\">Android:</strong> Settings → About phone → “Android version” (you need 12 or higher)." },
    specs_app_p2: { en: "Placeholder text to be replaced with the final content before publication." },
    box_1: { en: "Hagane lock" }, box_2: { en: "Charging cable" }, box_3: { en: "Reflective sticker" }, box_4: { en: "Quick install guide" }, box_5: { en: "Warranty certificate" },

    /* products page */
    prod_title1: { en: "Choose your" },
    prod1_desc: { en: "The 14 mm version, lighter (1.15 kg): the ideal choice for your bike. App unlock, Bluetooth LE, long-lasting battery." },
    prod2_desc: { en: "The tougher 18 mm version, heavier (2.05 kg): designed for scooters and motorbikes. App unlock, Bluetooth LE, long-lasting battery." },
    prod_secure: { en: "Secure payment with Stripe" },
    prod_add: { en: "Buy now" },
    prod_old: { en: "was" },

    /* legal shared */
    legal_back: { en: "← Back to home" },
    legal_updated: { en: "Last updated:" },
    privacy_title: { en: "Privacy Policy" },
    cookie_title: { en: "Cookie Policy" },
    terms_title: { en: "Terms of sale" }

,
    /* FAQ */
    faq_s1: { en: "Security" },
    faq_s1_q0: { en: "Why is Hagane safer than traditional locks?" },
    faq_s1_a0: { en: "Two reasons. The first is the mechanics: we designed the physical part of the lock using the same materials and manufacturing processes as the companies that build the most robust, high-performance security systems. The second is the electronics: by removing the traditional key lock, a would-be thief can't use the classic picking or lockpicking techniques. If you're not in the trade, picking means \"tickling\" a lock mechanism with tools that mimic the key. PS: you're not in the trade, right?" },
    faq_s1_q1: { en: "Can the lock be opened by a burglar?" },
    faq_s1_a1: { en: "We did our best to make a lock that's truly hard to force. A burglar using picking or lockpicking will find nothing to work with, because we removed the keyhole. The steel Hagane is built from is hardened to resist drilling and cutting. The tube diameter makes the common bolt cutters used on locks ineffective, and in the unlucky event Hagane is attacked with a hydraulic cutter, cutting times stretch considerably — which raises the odds the intruder gets caught." },
    faq_s1_q2: { en: "Is Hagane really that tough?" },
    faq_s1_a2: { en: "The Hagane 4218 reaches 10/10 for resistance on the scales of the certification bodies specialised in locks. The Hagane 3414 sits at 8/10. Right now, both locks are going through the official certification procedures." },
    faq_s1_q3: { en: "Can Hagane be hacked / opened by a hacker?" },
    faq_s1_a3: { en: "No: the encryption we use for the communication between phone and lock is based on bank-grade security algorithms. Should an intruder smash Hagane's button, the electronics lock the device down, preventing it from opening in any case." },
    faq_s1_q4: { en: "What warranty does the lock have?" },
    faq_s1_a4: { en: "Hagane's warranty lasts 4 years." },
    faq_s1_q5: { en: "Is Hagane certified?" },
    faq_s1_a5: { en: "Almost. Right now both locks are going through the official Sold Secure (United Kingdom) and ART (Netherlands) certification procedures. These bodies are the two top authorities on lock security." },
    faq_s2: { en: "Technology" },
    faq_s2_q0: { en: "To open the lock do I have to unlock my phone and use the app?" },
    faq_s2_a0: { en: "No: the app and the lock communicate over Bluetooth. What's more, when both Bluetooth and the hands-free feature are on, you can control Hagane without even taking your phone out of your jeans pocket — just keep the app in the background." },
    faq_s2_q1: { en: "Can I use traditional keys instead of the app?" },
    faq_s2_a1: { en: "No: Hagane is keyless, and we removed the traditional keyhole." },
    faq_s2_q2: { en: "Can I use the lock with more than one phone?" },
    faq_s2_a2: { en: "Yes: up to 3." },
    faq_s2_q3: { en: "To use my Hagane do I need to keep the app in the background?" },
    faq_s2_a3: { en: "Yes: app running in the background and Bluetooth on. That's all we ask. Don't worry — you'll be alerted if you happen to have Bluetooth off or the app not running." },
    faq_s2_q4: { en: "Does my phone need an internet connection to unlock the lock?" },
    faq_s2_a4: { en: "No: it's enough to have Bluetooth on." },
    faq_s2_q5: { en: "Do I have to keep my phone's Bluetooth on to unlock the lock?" },
    faq_s2_a5: { en: "Yes." },
    faq_s3: { en: "Battery & power" },
    faq_s3_q0: { en: "What if my phone dies? Is there a way to unlock Hagane?" },
    faq_s3_a0: { en: "Yes: install the Hagane app on another phone and restore your certificate from the cloud backup (iCloud Keychain on iOS, Google Block Store on Android) or from the .hbp backup file you exported. Once restored, you'll unlock the lock just like with your usual phone." },
    faq_s3_q1: { en: "How long does the battery last?" },
    faq_s3_a1: { en: "Expected battery life is 2 years at 20 unlocks a day. To avoid damaging the battery, Hagane should operate between -20° and 80°. Barring a worsening of climate change, that should be plenty." },
    faq_s3_q2: { en: "What happens if the lock runs out of charge?" },
    faq_s3_a2: { en: "Hagane's battery can be replaced. The procedure is the same used for safe locks — so don't think we skimped on security! But since we like simple things, you can do the swap yourself." },
    faq_s3_q3: { en: "Can I see how much battery is left and when to replace it?" },
    faq_s3_a3: { en: "No: you can't see the current charge level, but the app will send you a notification when it's time to change the battery." },
    faq_s4: { en: "App" },
    faq_s4_q0: { en: "Is the Hagane app password protected?" },
    faq_s4_a0: { en: "No: Hagane is standalone — it asks for no email and no password and talks to no server. Your unlock certificate is generated locally on the phone when you register the lock and protected by the device credentials (Face ID / Touch ID / device PIN). To move it to another phone, use the automatic cloud backup or export an .hbp file encrypted with your passphrase." },
    faq_s4_q1: { en: "Why can't I find my Hagane in the Bluetooth device list?" },
    faq_s4_a1: { en: "Because Hagane uses the Bluetooth Low Energy protocol. Since we'd rather you were out on your bike or motorbike, we won't get into boring technical details: just know that ultra-low-power devices never show up in the phone's list of connected devices." },
    faq_s5: { en: "Other" },
    faq_s5_q0: { en: "What happens if I lose my phone?" },
    faq_s5_a0: { en: "Install Hagane on a new phone and restore your profile from the cloud backup (iCloud Keychain on iOS, Google Block Store on Android) or from the .hbp file you exported. Once restored, you'll unlock the lock as before. Important: the certificate is kept only on your phone — if you lose it without a backup, the lock stays bound to the original certificate and must be taken in for service to be reinitialised." },
    faq_s5_q1: { en: "Does my Hagane need any maintenance?" },
    faq_s5_a1: { en: "Although Hagane needs no maintenance, it benefits from the same care you give your bike or motorbike chain. On top of that, simply changing the battery every 2 years will ensure your lock a long life." },
    faq_s5_q2: { en: "Can I see where I locked the lock?" },
    faq_s5_a2: { en: "Yes: the Hagane app includes an automatic parking feature (geolocation). Every time you lock and unlock, the position is recorded. You can view it from the app." },
    faq_s5_q3: { en: "Can I know in real time if my bike, kick scooter, scooter or motorbike is being stolen?" },
    faq_s5_a3: { en: "No: we'd rather not be the ones to break the bad news… which, with Hagane, shouldn't come anyway." },
    faq_title1: { en: "Frequently asked" },
    faq_title2: { en: "questions" }
,
    /* LEGAL */
    pr_0: { en: "Last updated: January 2026." },
    pr_1: { en: "This policy describes how HGN S.r.l. processes the personal data of users who visit this website and purchase Hagane products, pursuant to Regulation (EU) 2016/679 (GDPR)." },
    pr_2: { en: "<strong>Data controller.</strong> HGN S.r.l., Corso Lodi 18, 20135 Milan (MI), Italy — VAT and tax code 11577150961 — PEC: hgn@pec.it — email: info@hagane.it." },
    pr_3: { en: "<strong>Data collected.</strong> Browsing data (IP address, browser type, pages visited) and, in case of purchase, the data needed to fulfil the order (name, shipping and billing address, email). Payment data is handled directly by Stripe and is not stored by HGN S.r.l." },
    pr_4: { en: "<strong>Purposes.</strong> Data is processed to manage orders and shipping, to comply with legal obligations (tax and accounting), to respond to support requests and, subject to consent, to send informational communications." },
    pr_5: { en: "<strong>Legal basis.</strong> Performance of the sales contract, compliance with legal obligations, the controller's legitimate interest and consent, where required." },
    pr_6: { en: "<strong>Retention.</strong> Data is kept for the time needed for the stated purposes and within the terms required by law, in particular for tax obligations." },
    pr_7: { en: "<strong>Your rights.</strong> You may exercise at any time the rights of access, rectification, erasure, restriction, portability and objection, as well as the right to lodge a complaint with the data protection authority." },
    pr_8: { en: "<strong>Hagane app.</strong> The app is standalone: it requires no registration, uses no account and communicates with no server. The unlock certificate is generated and stored solely on your device." },
    pr_9: { en: "<strong>Contacts.</strong> For any request regarding your data: info@hagane.it." },
    ck_0: { en: "Last updated: January 2026." },
    ck_1: { en: "This website uses cookies to ensure it works correctly and to improve your browsing experience." },
    ck_2: { en: "<strong>What cookies are.</strong> Cookies are small text files that websites save on the user's device to store information such as the selected language or browsing preferences." },
    ck_3: { en: "<strong>Technical cookies.</strong> Necessary for the site to work, for example to remember the chosen language. They require no consent and cannot be disabled." },
    ck_4: { en: "<strong>Third-party cookies.</strong> During checkout, the Stripe payment service may set cookies needed to process the transaction securely and to prevent fraud." },
    ck_5: { en: "<strong>Analytics cookies.</strong> If enabled, they help us understand in aggregate form how the site is used. They are set only with prior consent." },
    ck_6: { en: "<strong>Managing preferences.</strong> You can change or delete cookies at any time from your browser settings. Disabling technical cookies may impair some site functions." },
    ck_7: { en: "<strong>Contacts.</strong> For information: info@hagane.it." },
    cd_0: { en: "Last updated: January 2026." },
    cd_1: { en: "These terms govern the sale of Hagane products through this website, operated by HGN S.r.l. By placing an order the user declares to have read and accepted them." },
    cd_2: { en: "<strong>1. Scope.</strong> These terms apply to all orders of Hagane products placed through the website by consumers." },
    cd_3: { en: "<strong>2. Prices and payments.</strong> All prices are in euros and include VAT. Payments are handled securely through Stripe; HGN S.r.l. does not store card data." },
    cd_4: { en: "<strong>3. Shipping and delivery.</strong> Products are shipped to the address indicated by the user within the times communicated at the time of the order. Any shipping costs are shown before confirmation." },
    cd_5: { en: "<strong>4. Right of withdrawal.</strong> The consumer may withdraw from the purchase within 14 days of delivery, without giving reasons, in accordance with the Italian Consumer Code. The product must be returned intact in its original packaging." },
    cd_6: { en: "<strong>5. Warranty.</strong> All products are covered by the 24-month legal warranty of conformity provided by law. The commercial warranty stated in the FAQ also applies." },
    cd_7: { en: "<strong>6. Governing law and jurisdiction.</strong> The contract is governed by Italian law. For consumers, the court of their place of residence or domicile has jurisdiction; in other cases, the Court of Milan." },
    cd_8: { en: "<strong>Contacts.</strong> For support: info@hagane.it." }
    /* @APPEND_DICT@ */
  };

  function captureOriginals() {
    document.querySelectorAll("[data-i18n],[data-i18n-html]").forEach(function (el) {
      var attr = el.getAttribute("data-i18n-attr");
      if (attr) el.__i18nOrig = el.getAttribute(attr);
      else if (el.hasAttribute("data-i18n-html")) el.__i18nOrig = el.innerHTML;
      else el.__i18nOrig = el.textContent;
    });
  }

  function applyLang(lang) {
    document.documentElement.setAttribute("lang", lang);
    document.querySelectorAll("[data-i18n],[data-i18n-html]").forEach(function (el) {
      var key = el.getAttribute("data-i18n") || el.getAttribute("data-i18n-html");
      var attr = el.getAttribute("data-i18n-attr");
      var val;
      if (lang === "it") { val = el.__i18nOrig; }
      else { val = DICT[key] && DICT[key][lang]; if (val == null) val = el.__i18nOrig; }
      if (val == null) return;
      if (attr) el.setAttribute(attr, val);
      else if (el.hasAttribute("data-i18n-html")) el.innerHTML = val;
      else el.textContent = val;
    });
    document.querySelectorAll(".lang-switch a[data-lang]").forEach(function (a) {
      a.classList.toggle("active", a.getAttribute("data-lang") === lang);
    });
    try { localStorage.setItem("hagane_lang", lang); } catch (e) {}
  }

  function initLang() {
    captureOriginals();
    var saved = "it";
    try { saved = localStorage.getItem("hagane_lang") || "it"; } catch (e) {}
    if (saved !== "it") applyLang(saved);
    else applyLang("it");
    document.querySelectorAll(".lang-switch a[data-lang]").forEach(function (a) {
      a.addEventListener("click", function (e) { e.preventDefault(); applyLang(a.getAttribute("data-lang")); });
    });
  }
  if (document.readyState !== "loading") initLang();
  else document.addEventListener("DOMContentLoaded", initLang);

  /* expose for the per-page dict appends */
  window.HAGANE_DICT = DICT;
})();

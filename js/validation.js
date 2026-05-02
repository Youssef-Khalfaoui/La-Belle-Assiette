(function () {
  "use strict";

  const RULES = {
    required: (v) => v.trim().length > 0,
    minLen: (n) => (v) => v.trim().length >= n,
    maxLen: (n) => (v) => v.trim().length <= n,
    email: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()),
    phone: (v) => !v.trim() || /^[\d\s\+\-\(\)\.]{7,20}$/.test(v.trim()),
    phoneReq: (v) => /^[\d\s\+\-\(\)\.]{7,20}$/.test(v.trim()),
    password: (v) => v.length >= 6,
    posNumber: (v) => parseFloat(v) > 0,
    address: (v) => v.trim().length >= 5,
    name: (v) => /^[a-zA-ZÀ-ÿ\s\-']{2,}$/.test(v.trim()),
    noScript: (v) => !/<script/i.test(v),
  };

  function getWrapper(field) {
    if (field.parentElement.classList.contains("v-wrap"))
      return field.parentElement;
    const parent = field.parentElement;
    const wrap = document.createElement("div");
    wrap.className = "v-wrap";
    parent.insertBefore(wrap, field);
    wrap.appendChild(field);
    const icon = document.createElement("span");
    icon.className = "v-icon";
    wrap.appendChild(icon);
    return wrap;
  }

  function setValid(field) {
    const wrap = getWrapper(field);
    wrap.classList.remove("v-invalid");
    wrap.classList.add("v-valid");
    const icon = wrap.querySelector(".v-icon");
    if (icon) icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
    const msg = wrap.querySelector(".v-msg");
    if (msg) msg.remove();
  }

  function setInvalid(field, message) {
    const wrap = getWrapper(field);
    wrap.classList.remove("v-valid");
    wrap.classList.add("v-invalid");
    const icon = wrap.querySelector(".v-icon");
    if (icon) icon.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i>';
    let msg = wrap.querySelector(".v-msg");
    if (!msg) {
      msg = document.createElement("span");
      msg.className = "v-msg";
      wrap.appendChild(msg);
    }
    msg.textContent = message;
    field.classList.remove("v-shake");
    void field.offsetWidth;
    field.classList.add("v-shake");
  }

  function clearState(field) {
    if (!field.parentElement.classList.contains("v-wrap")) return;
    const wrap = field.parentElement;
    wrap.classList.remove("v-valid", "v-invalid");
    const icon = wrap.querySelector(".v-icon");
    if (icon) icon.innerHTML = "";
    const msg = wrap.querySelector(".v-msg");
    if (msg) msg.remove();
  }

  function createValidator(container, fieldDefs) {
    function getField(def) {
      return def.el || container.querySelector(def.selector);
    }

    function validateField(def) {
      const el = getField(def);
      if (!el) return true;
      const val = el.value;

      if (def.optional && !val.trim()) {
        clearState(el);
        return true;
      }

      for (const rule of def.rules) {
        if (!rule.fn(val)) {
          setInvalid(el, rule.msg);
          return false;
        }
      }
      setValid(el);
      return true;
    }

    function attachLive() {
      fieldDefs.forEach((def) => {
        const el = getField(def);
        if (!el) return;
        getWrapper(el);

        el.addEventListener("blur", () => validateField(def));
        el.addEventListener("input", () => {
          if (el.parentElement.classList.contains("v-invalid"))
            validateField(def);
        });
      });
    }

    function validateAll() {
      let allOk = true;
      let firstBad = null;
      fieldDefs.forEach((def) => {
        const ok = validateField(def);
        if (!ok) {
          allOk = false;
          if (!firstBad) firstBad = getField(def);
        }
      });
      if (firstBad) firstBad.focus();
      return allOk;
    }

    return { attachLive, validateAll };
  }

  function attachPasswordStrength(inputEl) {
    if (!inputEl) return;

    const wrap = getWrapper(inputEl);
    const meter = document.createElement("div");
    meter.className = "pwd-strength";
    meter.innerHTML = `
      <div class="pwd-bar-track">
        <div class="pwd-bar-fill" id="pwd-bar"></div>
      </div>
      <span class="pwd-label" id="pwd-label"></span>`;
    wrap.parentElement.insertBefore(meter, wrap.nextSibling);

    const bar = meter.querySelector("#pwd-bar");
    const label = meter.querySelector("#pwd-label");

    function score(pw) {
      let s = 0;
      if (pw.length >= 6) s++;
      if (pw.length >= 10) s++;
      if (/[A-Z]/.test(pw)) s++;
      if (/[0-9]/.test(pw)) s++;
      if (/[^A-Za-z0-9]/.test(pw)) s++;
      return s;
    }

    const levels = [
      { w: "20%", color: "#e53935", text: "Très faible" },
      { w: "40%", color: "#fb8c00", text: "Faible" },
      { w: "60%", color: "#fdd835", text: "Moyen" },
      { w: "80%", color: "#43a047", text: "Fort" },
      { w: "100%", color: "#1b5e20", text: "Très fort" },
    ];

    inputEl.addEventListener("input", () => {
      const s = Math.min(score(inputEl.value), 5);
      if (!inputEl.value) {
        bar.style.width = "0";
        label.textContent = "";
        return;
      }
      const lv = levels[s - 1] || levels[0];
      bar.style.width = lv.w;
      bar.style.background = lv.color;
      label.textContent = lv.text;
      label.style.color = lv.color;
    });
  }

  function attachCharCounter(el, max) {
    if (!el) return;
    const counter = document.createElement("span");
    counter.className = "char-counter";
    counter.textContent = `0 / ${max}`;
    el.parentElement.appendChild(counter);
    el.addEventListener("input", () => {
      const n = el.value.length;
      counter.textContent = `${n} / ${max}`;
      counter.classList.toggle("char-over", n > max);
    });
  }

  function attachPhoneFormat(el) {
    if (!el) return;
    el.addEventListener("input", () => {
      let v = el.value.replace(/[^\d+]/g, "");
      if (v.startsWith("+")) {
        v = v.slice(0, 15);
      } else {
        v = v.slice(0, 10);
        v = v.replace(/(\d{2})(?=\d)/g, "$1 ").trim();
      }
      const pos = el.selectionStart;
      el.value = v;
      try {
        el.setSelectionRange(pos, pos);
      } catch (_) {}
    });
  }

  function attachPasswordToggle(inputEl) {
    if (!inputEl) return;
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "pwd-toggle";
    btn.setAttribute("aria-label", "Afficher/masquer le mot de passe");
    btn.innerHTML = '<i class="fa-solid fa-eye"></i>';

    const wrap = getWrapper(inputEl);
    wrap.appendChild(btn);

    btn.addEventListener("click", () => {
      const show = inputEl.type === "password";
      inputEl.type = show ? "text" : "password";
      btn.innerHTML = show
        ? '<i class="fa-solid fa-eye-slash"></i>'
        : '<i class="fa-solid fa-eye"></i>';
      inputEl.focus();
    });
  }

  function initConnexion() {
    const form = document
      .querySelector('form input[name="action"][value="connexion"]')
      ?.closest("form");
    if (!form) return;

    const emailEl = form.querySelector('input[name="email"]');
    const passEl = form.querySelector('input[name="mot_de_passe"]');

    attachPasswordToggle(passEl);

    const validator = createValidator(form, [
      {
        el: emailEl,
        rules: [
          { fn: RULES.required, msg: "L'adresse e-mail est obligatoire." },
          {
            fn: RULES.email,
            msg: "Format d'e-mail invalide (ex: nom@domaine.com).",
          },
        ],
      },
      {
        el: passEl,
        rules: [
          { fn: RULES.required, msg: "Le mot de passe est obligatoire." },
          { fn: RULES.minLen(4), msg: "Mot de passe trop court." },
        ],
      },
    ]);

    validator.attachLive();

    form.addEventListener("submit", (e) => {
      if (!validator.validateAll()) e.preventDefault();
    });
  }

  function initInscription() {
    const form = document
      .querySelector('form input[name="action"][value="inscription"]')
      ?.closest("form");
    if (!form) return;

    const prenomEl = form.querySelector('input[name="prenom"]');
    const nomEl = form.querySelector('input[name="nom"]');
    const emailEl = form.querySelector('input[name="email"]');
    const passEl = form.querySelector('input[name="mot_de_passe"]');
    const telEl = form.querySelector('input[name="telephone"]');
    const adresseEl = form.querySelector(
      'textarea[name="adresse"], input[name="adresse"]',
    );

    attachPasswordToggle(passEl);
    attachPasswordStrength(passEl);
    attachPhoneFormat(telEl);
    if (adresseEl) attachCharCounter(adresseEl, 200);

    const validator = createValidator(form, [
      {
        el: prenomEl,
        rules: [
          { fn: RULES.required, msg: "Le prénom est obligatoire." },
          {
            fn: RULES.name,
            msg: "Prénom invalide (lettres uniquement, min. 2 caractères).",
          },
          {
            fn: RULES.maxLen(50),
            msg: "Prénom trop long (max. 50 caractères).",
          },
        ],
      },
      {
        el: nomEl,
        rules: [
          { fn: RULES.required, msg: "Le nom est obligatoire." },
          {
            fn: RULES.name,
            msg: "Nom invalide (lettres uniquement, min. 2 caractères).",
          },
          { fn: RULES.maxLen(50), msg: "Nom trop long (max. 50 caractères)." },
        ],
      },
      {
        el: emailEl,
        rules: [
          { fn: RULES.required, msg: "L'adresse e-mail est obligatoire." },
          {
            fn: RULES.email,
            msg: "Format d'e-mail invalide (ex: nom@domaine.com).",
          },
        ],
      },
      {
        el: passEl,
        rules: [
          { fn: RULES.required, msg: "Le mot de passe est obligatoire." },
          {
            fn: RULES.password,
            msg: "Mot de passe trop court (minimum 6 caractères).",
          },
          { fn: RULES.maxLen(128), msg: "Mot de passe trop long." },
        ],
      },
      {
        el: telEl,
        optional: true,
        rules: [
          {
            fn: RULES.phone,
            msg: "Numéro invalide (ex: +216 12 345 678 ou 06 12 34 56 78).",
          },
        ],
      },
      {
        el: adresseEl,
        optional: true,
        rules: [
          {
            fn: RULES.maxLen(200),
            msg: "Adresse trop longue (max. 200 caractères).",
          },
          {
            fn: RULES.noScript,
            msg: "Caractères non autorisés dans l'adresse.",
          },
        ],
      },
    ]);

    validator.attachLive();

    form.addEventListener("submit", (e) => {
      if (!validator.validateAll()) e.preventDefault();
    });
  }

  function initCommande() {
    const form = document.getElementById("form-commande");
    if (!form) return;

    const adresseEl = form.querySelector(
      'input[name="adresse"], textarea[name="adresse"]',
    );
    const telEl = form.querySelector('input[name="telephone"]');
    const notesEl = form.querySelector('textarea[name="notes"]');

    attachPhoneFormat(telEl);
    if (notesEl) attachCharCounter(notesEl, 300);

    const validator = createValidator(form, [
      {
        el: adresseEl,
        rules: [
          {
            fn: RULES.required,
            msg: "L'adresse de livraison est obligatoire.",
          },
          {
            fn: RULES.address,
            msg: "Adresse trop courte (minimum 5 caractères).",
          },
          {
            fn: RULES.maxLen(200),
            msg: "Adresse trop longue (max. 200 caractères).",
          },
          { fn: RULES.noScript, msg: "Caractères non autorisés." },
        ],
      },
      {
        el: telEl,
        rules: [
          {
            fn: RULES.required,
            msg: "Le numéro de téléphone est obligatoire.",
          },
          {
            fn: RULES.phoneReq,
            msg: "Numéro invalide (ex: +216 12 345 678 ou 0612345678).",
          },
        ],
      },
      {
        el: notesEl,
        optional: true,
        rules: [
          {
            fn: RULES.maxLen(300),
            msg: "Note trop longue (max. 300 caractères).",
          },
          { fn: RULES.noScript, msg: "Caractères non autorisés." },
        ],
      },
    ]);

    validator.attachLive();

    form.addEventListener(
      "submit",
      function (e) {
        if (!validator.validateAll()) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      },
      true,
    );
  }

  function initAdminPlats() {
    const form = document.getElementById("form-plat");
    if (!form) return;

    const nomEl = document.getElementById("input-nom");
    const descEl = document.getElementById("input-description");
    const prixEl = document.getElementById("input-prix");
    const imageEl = document.getElementById("input-image");

    if (descEl) attachCharCounter(descEl, 300);

    const validator = createValidator(form, [
      {
        el: nomEl,
        rules: [
          { fn: RULES.required, msg: "Le nom du plat est obligatoire." },
          {
            fn: RULES.minLen(2),
            msg: "Nom trop court (minimum 2 caractères).",
          },
          {
            fn: RULES.maxLen(100),
            msg: "Nom trop long (maximum 100 caractères).",
          },
          { fn: RULES.noScript, msg: "Caractères non autorisés dans le nom." },
        ],
      },
      {
        el: descEl,
        optional: true,
        rules: [
          {
            fn: RULES.maxLen(300),
            msg: "Description trop longue (maximum 300 caractères).",
          },
          {
            fn: RULES.noScript,
            msg: "Caractères non autorisés dans la description.",
          },
        ],
      },
      {
        el: prixEl,
        rules: [
          { fn: RULES.required, msg: "Le prix est obligatoire." },
          {
            fn: RULES.posNumber,
            msg: "Le prix doit être un nombre positif (ex: 12.50).",
          },
          {
            fn: (v) => parseFloat(v) <= 999,
            msg: "Prix trop élevé (maximum 999 €).",
          },
        ],
      },
    ]);

    if (imageEl) {
      imageEl.addEventListener("change", () => {
        const file = imageEl.files[0];
        if (!file) return;
        const allowed = ["image/jpeg", "image/png", "image/webp"];
        if (!allowed.includes(file.type)) {
          setInvalid(
            imageEl,
            "Format non supporté. Utilisez JPG, PNG ou WEBP.",
          );
          imageEl.value = "";
          return;
        }
        if (file.size > 2 * 1024 * 1024) {
          setInvalid(imageEl, "Image trop lourde (maximum 2 Mo).");
          imageEl.value = "";
          return;
        }
        setValid(imageEl);
      });
    }

    validator.attachLive();

    form.addEventListener("submit", (e) => {
      if (!validator.validateAll()) e.preventDefault();
    });
  }

  function init() {
    initConnexion();
    initInscription();
    initCommande();
    initAdminPlats();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

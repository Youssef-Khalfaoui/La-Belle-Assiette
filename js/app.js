document.addEventListener("DOMContentLoaded", () => {
  const logoTop = document.getElementById("logo-top");
  logoTop?.addEventListener("click", (e) => {
    const target = document.getElementById("top");
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });

  const overlay = document.getElementById("overlay");
  const panierSidebar = document.getElementById("panier-sidebar");
  const btnOuvrirPanier = document.getElementById("btn-panier");
  const btnFermerPanier = document.getElementById("btn-fermer-panier");

  function ouvrirPanier() {
    overlay?.classList.add("visible");
    panierSidebar?.classList.add("ouvert");
    document.body.style.overflow = "hidden";
    chargerPanier();
  }

  function fermerPanier() {
    overlay?.classList.remove("visible");
    panierSidebar?.classList.remove("ouvert");
    document.body.style.overflow = "";
  }

  btnOuvrirPanier?.addEventListener("click", ouvrirPanier);
  btnFermerPanier?.addEventListener("click", fermerPanier);
  overlay?.addEventListener("click", fermerPanier);

  const ingrBackdrop = document.getElementById("ingr-modal-backdrop");
  const ingrClose = document.getElementById("ingr-modal-close");
  const ingrCancel = document.getElementById("ingr-btn-cancel");
  const ingrConfirm = document.getElementById("ingr-btn-confirm");
  const ingrSection = document.getElementById("ingr-section");
  const ingrListe = document.getElementById("ingr-liste");
  const ingrDescBox = document.getElementById("ingr-desc-box");
  const ingrNotes = document.getElementById("ingr-notes");
  const ingrCountEl = document.getElementById("ingr-count");
  let ingrPlatId = null;

  function openIngr(btn) {
    ingrPlatId = btn.dataset.id;
    const nom = btn.dataset.nom || "";
    const prix = parseFloat(btn.dataset.prix || 0);
    const cat = btn.dataset.cat || "";
    const desc = btn.dataset.desc || "";
    const imgSrc = btn.dataset.image || "";

    document.getElementById("ingr-modal-nom").textContent = nom;
    document.getElementById("ingr-modal-prix").textContent =
      prix.toFixed(2) + " \u20ac";
    document.getElementById("ingr-modal-cat").innerHTML =
      `<i class="fa-solid fa-tag"></i> ${cat}`;

    const imgEl = document.getElementById("ingr-modal-img");
    const fbEl = document.getElementById("ingr-img-fallback");
    if (imgSrc) {
      imgEl.src = imgSrc;
      imgEl.style.display = "block";
      fbEl.style.display = "none";
      imgEl.onerror = () => {
        imgEl.style.display = "none";
        fbEl.style.display = "flex";
      };
    } else {
      imgEl.style.display = "none";
      fbEl.style.display = "flex";
    }

    const parts = desc
      .split(",")
      .map((s) => s.trim())
      .filter((s) => s.length > 0 && s.length <= 60);
    const showIngr = parts.length >= 2;

    if (showIngr) {
      ingrSection.style.display = "";
      ingrDescBox.style.borderTop = "1px solid var(--bordure)";
      ingrListe.innerHTML = parts
        .map(
          (ing) =>
            `<div class="ingr-item checked" data-ing="${ing.replace(/"/g, "&quot;")}">\n               <span class="ingr-checkbox"><i class="fa-solid fa-check" style="font-size:0.7rem"></i></span>\n               <span class="ingr-name">${ing}</span>\n             </div>`,
        )
        .join("");
      ingrListe.querySelectorAll(".ingr-item").forEach((item) => {
        item.addEventListener("click", () => {
          item.classList.toggle("checked");
          updateIngrCount();
        });
      });
    } else {
      ingrSection.style.display = "none";
      ingrDescBox.style.borderTop = "none";
      ingrListe.innerHTML = "";
    }

    ingrNotes.value = "";
    updateIngrCount();

    ingrBackdrop.classList.add("open");
    document.body.style.overflow = "hidden";
  }

  function closeIngr() {
    ingrBackdrop.classList.remove("open");
    document.body.style.overflow = "";
    ingrPlatId = null;
  }

  function updateIngrCount() {
    const all = ingrListe.querySelectorAll(".ingr-item");
    const checked = ingrListe.querySelectorAll(".ingr-item.checked");
    if (all.length === 0) {
      ingrCountEl.textContent = "";
      ingrCountEl.classList.remove("has-selection");
      return;
    }
    if (checked.length === all.length) {
      ingrCountEl.textContent = "Tous les ingr\u00e9dients inclus";
      ingrCountEl.classList.remove("has-selection");
    } else if (checked.length === 0) {
      ingrCountEl.textContent = "Aucun ingr\u00e9dient s\u00e9lectionn\u00e9";
      ingrCountEl.classList.add("has-selection");
    } else {
      const removed = all.length - checked.length;
      ingrCountEl.textContent = `${removed} ingr\u00e9dient${removed > 1 ? "s" : ""} retir\u00e9${removed > 1 ? "s" : ""}`;
      ingrCountEl.classList.add("has-selection");
    }
  }

  ingrClose?.addEventListener("click", closeIngr);
  ingrCancel?.addEventListener("click", closeIngr);
  ingrBackdrop?.addEventListener("click", (e) => {
    if (e.target === ingrBackdrop) closeIngr();
  });

  ingrConfirm?.addEventListener("click", async () => {
    if (!ingrPlatId) return;

    const allItems = [...ingrListe.querySelectorAll(".ingr-item")];
    const checkedItems = [...ingrListe.querySelectorAll(".ingr-item.checked")];
    const ingredients =
      allItems.length > 0 && checkedItems.length < allItems.length
        ? checkedItems.map((el) => el.dataset.ing).join(", ")
        : "";
    const instructions = ingrNotes.value.trim();

    ingrConfirm.disabled = true;
    ingrConfirm.dataset.state = "loading";

    try {
      const params = new URLSearchParams({
        action: "ajouter",
        plat_id: ingrPlatId,
        quantite: 1,
        ingredients,
        instructions,
      });
      const res = await fetch("php/panier.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: params.toString(),
      });
      const text = await res.text();
      let data;
      try {
        const j = text.indexOf("{");
        data = JSON.parse(j >= 0 ? text.slice(j) : text);
      } catch {
        throw new Error("R\u00e9ponse invalide du serveur");
      }

      if (data.succes) {
        mettreAJourBadge(data.nb_articles);
        afficherToast("Ajout\u00e9 au panier !", "succes");
        closeIngr();
      } else {
        afficherToast(data.message || "Erreur", "erreur");
      }
    } catch (e) {
      afficherToast("Erreur : " + e.message, "erreur");
    } finally {
      ingrConfirm.disabled = false;
      delete ingrConfirm.dataset.state;
    }
  });

  document.querySelectorAll(".btn-ajouter").forEach((btn) => {
    btn.addEventListener("click", () => openIngr(btn));
  });

  async function chargerPanier() {
    const contenu = document.getElementById("panier-items");
    const footer = document.getElementById("panier-footer");
    if (!contenu) return;

    try {
      const res = await fetch("php/panier.php?action=lire");
      const data = await res.json();
      const items = data.items || [];

      if (items.length === 0) {
        contenu.innerHTML = `
          <div class="panier-vide">
            <div class="panier-vide-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <p>Votre panier est vide</p>
            <small style="color:var(--gris)">Ajoutez des plats pour commencer</small>
          </div>`;
        if (footer) footer.style.display = "none";
        return;
      }

      let html = "";
      items.forEach((item) => {
        const sous = item.prix * item.quantite;
        const imgHtml = item.image
          ? `<img src="${item.image}" alt="${item.nom}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
          : "";
        const iconHtml = item.image
          ? `<i class="fa-solid fa-utensils" style="display:none"></i>`
          : `<i class="fa-solid fa-utensils"></i>`;
        html += `
          <div class="panier-item" id="item-${item.id}">
            <div class="panier-item-icon">
              ${imgHtml}${iconHtml}
            </div>
            <div class="panier-item-info">
              <h4>${item.nom}</h4>
              <span class="prix">${sous.toFixed(2)} €</span>
            </div>
            <div class="qte-controls">
              <button class="qte-btn minus" onclick="modifierQte(${item.id}, ${item.quantite - 1})"></button>
              <span class="qte-val">${item.quantite}</span>
              <button class="qte-btn plus" onclick="modifierQte(${item.id}, ${item.quantite + 1})"></button>
            </div>
          </div>`;
      });

      contenu.innerHTML = html;

      if (footer) {
        footer.style.display = "block";
        const total = data.total || 0;
        document.getElementById("total-ttc").textContent =
          total.toFixed(2) + " €";
        document.getElementById("total-livraison").textContent =
          total >= 30 ? "Gratuite" : "1.89 €";
        const totalFinal = total >= 30 ? total : total + 1.89;
        document.getElementById("total-final").textContent =
          totalFinal.toFixed(2) + " €";
      }
    } catch (e) {
      contenu.innerHTML = `<p style="color:var(--rouge);padding:1rem">Erreur de chargement du panier.</p>`;
    }
  }

  document.querySelectorAll(".filtre-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document
        .querySelectorAll(".filtre-btn")
        .forEach((b) => b.classList.remove("actif"));
      btn.classList.add("actif");

      const cat = btn.dataset.cat;
      document.querySelectorAll(".carte-plat").forEach((carte) => {
        const afficher = cat === "tous" || carte.dataset.cat === cat;
        carte.style.display = afficher ? "flex" : "none";
      });
    });
  });

  window.afficherToast = function (message, type = "succes") {
    let toast = document.getElementById("toast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "toast";
      toast.className = "toast";
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add("visible");
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.classList.remove("visible"), 3000);
  };

  window.mettreAJourBadge = function (nb) {
    const badge = document.getElementById("badge-panier");
    if (badge) {
      badge.textContent = nb;
      badge.style.display = nb > 0 ? "flex" : "none";
    }
  };

  window.modifierQte = async function (platId, nouvelleQte) {
    const res = await fetch("php/panier.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=modifier&plat_id=${platId}&quantite=${nouvelleQte}`,
    });
    const data = await res.json();
    if (data.succes) {
      mettreAJourBadge(data.nb_articles);
      chargerPanier();
    }
  };

  const btnCommander = document.getElementById("btn-commander");
  const modalOverlay = document.getElementById("modal-overlay");
  const modalFermer = document.getElementById("modal-fermer");
  const formCommande = document.getElementById("form-commande");

  btnCommander?.addEventListener("click", () => {
    fermerPanier();
    window.scrollTo({ top: 0, behavior: "instant" });
    document.body.style.overflow = "hidden";
    modalOverlay?.classList.add("visible");
  });
  modalFermer?.addEventListener("click", () => {
    modalOverlay?.classList.remove("visible");
    document.body.style.overflow = "";
  });
  modalOverlay?.addEventListener("click", (e) => {
    if (e.target === modalOverlay) {
      modalOverlay.classList.remove("visible");
      document.body.style.overflow = "";
    }
  });

  formCommande?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = formCommande.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.dataset.state = "loading";

    const fd = new FormData(formCommande);
    fd.append("action", "commander");

    try {
      const res = await fetch("php/panier.php", { method: "POST", body: fd });
      const data = await res.json();

      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }

      if (data.succes) {
        modalOverlay?.classList.remove("visible");
        document.body.style.overflow = "";
        afficherSuccesCommande(data.commande_id);
        mettreAJourBadge(0);
        chargerPanier();
      } else {
        afficherToast(data.message || "Erreur", "erreur");
        btn.disabled = false;
        delete btn.dataset.state;
      }
    } catch {
      afficherToast("Erreur réseau", "erreur");
      btn.disabled = false;
      delete btn.dataset.state;
    }
  });

  function afficherSuccesCommande(id) {
    const div = document.createElement("div");
    div.className = "modal-overlay visible";
    div.innerHTML = `
      <div class="modal">
        <div class="succes-commande">
          <div class="succes-icone"><i class="fa-solid fa-circle-check"></i></div>
          <h2>Commande confirmée !</h2>
          <p style="color:var(--gris);margin:1rem 0">
            Votre commande <strong>#${id}</strong> a été reçue.<br>
            Préparation estimée : <strong>25–40 minutes</strong>.
          </p>
          <a href="mes-commandes.php" class="btn btn-primaire"><i class="fa-solid fa-box-open"></i> Suivre ma commande</a>
          <br><br>
          <a href="index.php" style="color:var(--gris);font-size:0.9rem"><i class="fa-solid fa-arrow-left"></i> Retour au menu</a>
        </div>
      </div>`;
    document.body.appendChild(div);
    div.addEventListener("click", (e) => {
      if (e.target === div) div.remove();
    });
  }

  fetch("php/panier.php?action=compter")
    .then((r) => r.json())
    .then((d) => {
      if (d.nb_articles !== undefined) mettreAJourBadge(d.nb_articles);
    })
    .catch(() => {});
});

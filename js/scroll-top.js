(function () {
  var btn = document.createElement("button");
  btn.id = "scroll-top-btn";
  btn.setAttribute("aria-label", "Retour en haut");
  btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>';

  function init() {
    document.body.appendChild(btn);

    window.addEventListener(
      "scroll",
      function () {
        if (window.scrollY > 300) {
          btn.classList.add("visible");
        } else {
          btn.classList.remove("visible");
        }
      },
      { passive: true },
    );

    btn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

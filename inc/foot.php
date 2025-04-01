</div>
</div>
</div>
</main>
<style>
@import url("https://fonts.googleapis.com/css2?family=Roboto&display=swap");
@import url("https://fonts.googleapis.com/icon?family=Material+Icons+Outlined");

footer {
  position: fixed;
  bottom: 0;
  width: 100%;
  background-color: transparent; /* Исправлено с none на transparent */
  box-shadow: none;
  z-index: 1000;
}

:root {
  --accent-color: #424e37;
  --accent-color-fg: #fefefe;
  --backdrop-color: #424e37;
  --app-content-background-color: #424e37;
  --inset-shadow: rgba(7, 43, 74, 0.3);
  --outset-shadow: rgba(223, 240, 255, 0.25);
  --clay-box-shadow: rgba(7, 43, 74, 0.3);
  --clay-background-color: #424e37;
  --clay-fg-color: #fefefe;
}

.flex-center {
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.tabbar {
  height: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 -4px 8px 0 rgba(0, 0, 0, 0.2);
}

.tabbar ul {
  display: flex;
  justify-content: space-around;
  align-items: center;
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  background: #424e37;
}

.tabbar li {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 20%;
  height: 50px;
  cursor: pointer;
  color: #888;
  transition: all 0.3s ease;
  position: relative;
  border-top-left-radius: 100%;
  border-top-right-radius: 100%;
  background: #424e37;
}

.tabbar li span.icon {
  font-size: 1.5rem;
  color: #888;
  transition: transform 0.3s ease, color 0.3s ease;
  position: relative;
  z-index: 1;
}

.tabbar li span.label {
  font-size: 0.7rem;
  color: transparent;
  transition: color 0.3s ease;
  margin-top: 4px;
  position: relative;
  z-index: 1;
}

.tabbar li.active {
  top: -10px;
  height: 60px;
  width: 60px;
}

.tabbar li.active span.icon {
  color: #ffffff;
  transform: scale(1.2);
}

.tabbar li.active span.label {
  color: #ffffff;
}

.tabbar li.active::before {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 60px;
  background-color: #424e37;
  border-radius: 50%;
  z-index: -1;
  animation: bubble-up 0.3s ease-in-out;
}

@keyframes bubble-up {
  0% {
    transform: translateX(-50%) scale(0);
  }
  100% {
    transform: translateX(-50%) scale(1);
  }
}

.tabbar li:hover span.icon {
  color: #ffffff;
}

.tabbar a {
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Медиа-запрос для ПК (шире 768px) */
@media (min-width: 768px) {
  footer {
    width: 50%;
    border-radius: 2rem 2rem 2rem 2rem;
    left: 50%;
    transform: translateX(-50%);
  }

  .tabbar {
    height: 5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: none;
  }

  .tabbar li {
    display: flex;
    flex-direction: column;
    width: 20%;
    height: 57px;
    cursor: pointer;
    color: #888;
    transition: all 0.3s ease;
    position: relative;
    border-top-left-radius: 100%;
    border-top-right-radius: 100%;
    background: #424e3700; /* Прозрачный фон */
    justify-content: center;
  }

  .tabbar ul {
    border-radius: 2rem 2rem 2rem 2rem;
  }
}
</style>

<!-- Нижний навбар -->
<footer>
  <div class="tabbar">
    <ul class="flex-center">
      <li class="home" data-page="/index.php">
        <a href="/index.php">
          <span class="material-icons-outlined icon">home</span>
          <span class="label">Главная</span>
        </a>
      </li>
      <li class="search" data-page="/search_montaj.php">
        <a href="/search_montaj.php">
          <span class="material-icons-outlined icon">search</span>
          <span class="label">Поиск</span>
        </a>
      </li>
      <li class="add" data-page="/montaj.php">
        <a href="/montaj.php">
          <span class="material-icons-outlined icon">add</span>
          <span class="label">Добавить</span>
        </a>
      </li>
      <li class="profile" data-page="/user.php">
        <a href="/user.php">
          <span class="material-icons-outlined icon">person</span>
          <span class="label">Профиль</span>
        </a>
      </li>
      <li class="letter-n" data-page="/navigard/index.php">
        <a href="/navigard/">
          <img src="../img/MainLogo.png" width="32px" style="filter: grayscale(1);">
          <span class="label">Navigard</span>
        </a>
      </li>
    </ul>
  </div>
</footer>

<!-- JavaScript для навбара -->
<script>
const currentUrl = window.location.pathname;
const tabs = document.querySelectorAll(".tabbar li");

tabs.forEach((tab) => {
  const tabPage = tab.getAttribute("data-page");
  if (
    currentUrl === tabPage ||
    (tabPage.startsWith("/navigard") && currentUrl.startsWith("/navigard"))
  ) {
    tab.classList.add("active");
  }
  tab.addEventListener("click", () => {
    tabs.forEach((t) => t.classList.remove("active"));
    tab.classList.add("active");
  });
});
</script>

<script src="/js/bootstrap.min.js"></script>
<script src="/js/bootstrap-formhelpers-phone.js"></script>
<script src="/js/bootstrap-formhelpers.min.js"></script>
</body>
</html>
<script setup>
import { useRouter } from "vue-router";
import { ref, watchEffect } from "vue";

const router = useRouter();

// ИСПРАВЛЕНО: Теперь состояние авторизации инициализируется на основе токена в localStorage
const isAuth = ref(!!localStorage.getItem('token'));

// Отслеживаем изменения токена, если они происходят в процессе работы приложения
watchEffect(() => {
  isAuth.value = !!localStorage.getItem('token');
});

function logout(event) {
  // ИСПРАВЛЕНО: предотвращаем стандартный переход по ссылке роутера
  event.preventDefault();

  // Удаляем токен из хранилища
  localStorage.removeItem("token");
  isAuth.value = false;

  // Перенаправляем на страницу логина
  router.push("/login");
}
</script>

<template>
  <header class="header">
    <div class="container">
      <router-link to="/" class="brand">🍲 Кухни мира</router-link>
      <nav class="nav">
        <router-link to="/" active-class="active">Рецепты</router-link>
        <router-link to="/constructor" active-class="active">Конструктор</router-link>
        <!-- ИСПРАВЛЕНО: Заменили обычную ссылку .html на router-link (когда создадите этот роут) -->
        <span v-if="isAuth" class="only-auth" style="display:contents">
          <router-link to="/my/recipes">Мои рецепты</router-link>
        </span>
      </nav>

      <!-- Панель для авторизованных пользователей -->
      <div class="user-box only-auth" v-if="isAuth">
        <router-link class="btn btn-sm" to="/recipe/create">+ Новый рецепт</router-link>
        <!-- ИСПРАВЛЕНО: Убрали to="/logout", заменив на кнопку с событием, чтобы не вызывать ошибку роутера -->
        <button class="btn btn-light btn-sm" @click="logout">Выйти</button>
      </div>

      <!-- Панель для гостей -->
      <div class="user-box only-guest" v-else>
        <router-link class="btn btn-light btn-sm" to="/login">Войти</router-link>
        <router-link class="btn btn-sm" to="/register">Регистрация</router-link>
      </div>
    </div>
  </header>

  <!-- Контент страниц будет выводиться здесь -->
  <main class="container">
    <router-view />
  </main>

  <footer class="footer">© Кухни мира</footer>
</template>

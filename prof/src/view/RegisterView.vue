<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import api from "@/api.js";

const router = useRouter();
const form = reactive({ email: "", name: "", password: "" });
const errors = ref({})
const formErrors = ref('')

async function submit() {
  errors.value = {};
  formErrors.value = '';
  try {
    await api.post("/register", form);
    await router.push("/login");
  } catch (e) {
    const res = e.response
    if (res?.status === 422) {
      errors.value = res.data.errors;
    } else {
      formErrors.value = res?.data?.message || 'Ошибка соединения с сервером';
    }
  }
}
</script>

<template>
  <main class="page">
    <div class="container">
      <form class="card auth-card" @submit.prevent="submit" novalidate>
        <h1>Регистрация</h1>
        <p class="muted" style="margin:0 0 24px">Создайте аккаунт, чтобы публиковать рецепты</p>

        <div class="field" :class="{ 'has-error': errors.email }">
          <label for="email">Email</label>
          <input id="email" v-model="form.email" type="email" placeholder="you@example.com">
          <p v-if="errors.email" class="errors-text">{{ errors.email[0] }}</p>
        </div>

        <div class="field" :class="{ 'has-error': errors.name }">
          <label for="name">Имя</label>
          <input id="name" v-model="form.name" type="text" placeholder="">
          <p v-if="errors.name" class="errors-text">{{ errors.name[0] }}</p>
        </div>

        <div class="field" :class="{ 'has-error': errors.password }">
          <label for="password">Пароль</label>
          <input id="password" v-model="form.password" type="password" placeholder="">
          <p v-if="errors.password" class="errors-text">{{ errors.password[0] }}</p>
        </div>

        <button class="btn btn-block" type="submit">Зарегистрироваться</button>
        <div class="form-alert" v-if="formErrors">{{ formErrors }}</div>
        <p class="form-foot">Уже есть аккаунт? <router-link to="/login">Войти</router-link></p>
      </form>
    </div>
  </main>
</template>

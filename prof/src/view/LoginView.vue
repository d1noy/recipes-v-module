<script setup>

import { useRouter } from "vue-router";
import {ref,reactive} from "vue";
import api from "../api";

const router = useRouter();
const form = reactive({email:"", password: ""});
const errors = ref({});
const formErrors = ref('');

async function submit() {
  errors.value = {};
  formErrors.value = '';
  try {
    const {data} = await api.post("/login", form);
    localStorage.setItem("token", data.token);
    await router.push("/");
  } catch (e) {
    const res = e.response;
    if(res?.status === 422) errors.value = res.data.errors;
    else formErrors.value = res?.data?.message || 'Ошибка соединения с сервером';
  }
}
</script>

<template>
  <main class="page">
    <div class="container">
      <form class="card auth-card" @submit.prevent="submit" novalidate="">
        <h1>Вход</h1>
        <p class="muted" style="margin:0 0 24px">Войдите, чтобы создавать и редактировать рецепты</p>

        <div class="field" :class="{'has-error': errors.has}">
          <label for="email">Email</label>
          <input id="email" type="email" v-model="form.email" placeholder="you@example.com">
        </div>
        <div class="field" :class="{'has-error': errors.has}">
        <label for="password">Пароль</label>
        <input id="password" v-model="form.password"  type="password"  placeholder="">
          <p v-if="errors.password" class="errors-text">{{errors.password[0]}}</p>
      </div>
        <button class="btn btn-block" type="submit">Войти</button>
        <div class="form-alert" v-if="formErrors">{{ formErrors }}</div>
        <p class="form-foot">Нет аккаунта? <router-link to="/register">Зарегистрироваться</router-link></p>
      </form>
    </div>
  </main>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter, RouterLink } from "vue-router";
import api from "@/api.js";

const route = useRoute();
const router = useRouter();
const id = route.params.id;

const errors = ref([]);
const form = ref({
  title: "",
  description: "",
  calories: "",
  cooking_time: "",
  ingredients: [],
  image: null,
});
const imagePreview = ref(null);
const ingredients = ref([]);

function onImageChange(e) {
  const file = e.target.files[0];
  form.value.image = file;
  if (file) {
    imagePreview.value = URL.createObjectURL(file);
  }
}

function validate() {
  const e = {};

  if (!form.value.title.trim()) e.title = "Укажите название рецепта.";
  if (!form.value.description.trim()) e.description = "Поле обязательно для заполнения";
  if (form.value.calories === "" || isNaN(Number(form.value.calories)) || Number(form.value.calories) < 0)
    e.calories = "Калорийность должна быть числом.";
  if (!form.value.cooking_time || Number(form.value.cooking_time) < 1)
    e.cooking_time = "Укажите время приготовления";
  if (form.value.ingredients.length === 0) e.ingredients = "Выберите хотя бы один ингредиент";
  // image НЕ проверяем: при редактировании старая картинка уже стоит на сервере

  return e;
}

async function onSubmit() {
  errors.value = validate();
  if (Object.keys(errors.value).length) return;

  const data = new FormData();
  data.append("_method", "PUT"); // Laravel не читает multipart при PUT — шлём POST-ом
  data.append("title", form.value.title);
  data.append("description", form.value.description);
  data.append("calories", form.value.calories);
  data.append("cooking_time", form.value.cooking_time);
  form.value.ingredients.forEach(ingId => data.append("ingredients[]", ingId));
  if (form.value.image) data.append("image", form.value.image); // только если выбрали новую

  try {
    await api.post(`/recipes/${id}`, data);
    await router.push("/my/recipes");
  } catch (err) {
    errors.value = err.response?.data?.errors || { form: "Не удалось сохранить рецепт." };
  }
}

onMounted(async () => {
  const [recipeRes, ingRes] = await Promise.all([
    api.get(`/recipes/${id}`),
    api.get("/ingredients"),
  ]);

  const r = recipeRes.data.data ?? recipeRes.data;

  form.value.title = r.title;
  form.value.description = r.description;
  form.value.calories = r.calories;
  form.value.cooking_time = r.cooking_time;
  form.value.ingredients = (r.ingredients ?? []).map(i => i.id);
  imagePreview.value = r.image;

  ingredients.value = ingRes.data.data ?? ingRes.data;
});
</script>

<template>
  <div class="container">
    <div class="page-head"><h1>Редактирование рецепта</h1></div>

    <form class="card form" @submit.prevent="onSubmit">
      <div class="field" :class="{ 'has-error': errors.title }">
        <label for="title">Название</label>
        <input id="title" type="text" v-model="form.title" placeholder="Например, Борщ">
        <p class="error-text" v-if="errors.title">{{ errors.title }}</p>
      </div>

      <div class="field" :class="{ 'has-error': errors.description }">
        <label for="description">Описание</label>
        <textarea id="description" v-model="form.description"></textarea>
        <p class="error-text" v-if="errors.description">{{ errors.description }}</p>
      </div>

      <div class="grid-2">
        <div class="field" :class="{ 'has-error': errors.calories }">
          <label for="calories">Калорийность (ккал)</label>
          <input id="calories" type="number" v-model="form.calories" min="0">
          <p class="error-text" v-if="errors.calories">{{ errors.calories }}</p>
        </div>
        <div class="field" :class="{ 'has-error': errors.cooking_time }">
          <label for="cooking_time">Время приготовления (мин)</label>
          <input id="cooking_time" type="number" v-model="form.cooking_time" min="1">
          <p class="error-text" v-if="errors.cooking_time">{{ errors.cooking_time }}</p>
        </div>
      </div>

      <div class="field" :class="{ 'has-error': errors.image }">
        <label for="image">Изображение</label>
        <input id="image" type="file" accept="image/*" @change="onImageChange">
        <img v-if="imagePreview" class="img-preview" :src="imagePreview" alt="">
      </div>

      <fieldset class="field" :class="{ 'has-error': errors.ingredients }">
        <legend>Ингредиенты</legend>
        <div class="check-grid">
          <label class="check" v-for="ing in ingredients" :key="ing.id">
            <input type="checkbox" :value="ing.id" v-model="form.ingredients">
            {{ ing.name }}
          </label>
        </div>
        <p class="error-text" v-if="errors.ingredients">{{ errors.ingredients }}</p>
      </fieldset>

      <p class="error-text" v-if="errors.form">{{ errors.form }}</p>

      <div class="form-actions">
        <button class="btn" type="submit">Сохранить</button>
        <RouterLink class="btn btn-light" to="/my/recipes">Отмена</RouterLink>
      </div>
    </form>
  </div>
</template>


<style scoped>

</style>
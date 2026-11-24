<script setup>

import {onMounted, ref} from "vue";
import api from "@/api.js";
import {useRouter} from "vue-router";

const router = useRouter();
const errors = ref([]);
const form = ref({
  title: "",
  description: "",
  calories: "",
  cooking_time: "",
  ingredients: [],
  image: null,
})
const imagePreview = ref(null);
const ingredients = ref([]);
function onImageChange(e) {
  const file = e.target.files[0];
  form.value.image = file;
  if(file) {
    imagePreview.value = URL.createObjectURL(file);
  }
}

function validate() {
  const e = {};

  if (!form.value.title.trim()) e.title = "Укажите название рецепта.";
  if (!form.value.description.trim()) e.description = "Поле обязательно для заполнения";
  if (form.value.calories === '' || isNaN(Number(form.value.calories)) || Number(form.value.calories) < 0)
    e.calories = "Калорийность должна быть числом.";
  if (!form.value.cooking_time || Number(form.value.cooking_time) < 1)
    e.cooking_time = "Укажите время приготовления";
  if (form.value.ingredients.length === 0) e.ingredients = "Выберите хотя бы один ингредиент";
  if (!form.value.image) e.image = "Загрузите изображение.";
  return e
}

async function onSubmit() {
  errors.value = validate();
  if(Object.keys(errors.value).length) return;

  const data = new FormData();
  data.append("title", form.value.title);
  data.append("description", form.value.description);
  data.append("calories", form.value.calories);
  data.append("cooking_time", form.value.cooking_time);
  form.value.ingredients.forEach(id => data.append("ingredients[]", id));
  data.append("image", form.value.image);

  try {
    await api.post("/recipes", data);
    await router.push("/");

  } catch (err) {
    errors.value = err.response?.data?.errors || {form: "Не удалось сохранить рецепт."};
  }


}

onMounted(async () => {
  const res = await api.get("/ingredients");
  ingredients.value = res.data.data;
})


</script>

<template>
  <div class="container">
    <div class="page-head"><h1>Новый рецепт</h1></div>
    <form class="card form" @submit.prevent="onSubmit"  enctype="multipart/form-data" novalidate>
      <div class="field">
        <label for="title">Название</label>
        <input id="title" name="title" type="text" v-model.trim="form.title" value="" placeholder="Например, Борщ">
        <p v-if="errors.title" class="error-text">{{errors.title}}</p>
      </div>
      <div class="field">
        <label for="description">Описание</label>
        <textarea id="description" name="description" v-model.trim="form.description"></textarea>
        <p v-if="errors.description" class="error-text">{{errors.description}}</p>
      </div>
      <div class="grid-2">
        <div class="field">
          <label for="calories">Калорийность (ккал)</label>
          <input id="calories" name="calories" type="number" v-model="form.calories"  placeholder="" min="0">
          <p v-if="errors.calories" class="error-text">{{errors.calories}}</p>
        </div>
        <div class="field">
          <label for="cooking_time">Время приготовления (мин)</label>
          <input id="cooking_time" name="cooking_time" type="number" v-model="form.cooking_time"  placeholder="" min="1">
          <p v-if="errors.cooking_time" class="error-text">{{errors.cooking_time}}</p>
        </div>
      </div>
      <div class="field">
        <label for="image">Изображение</label>
        <input id="image" name="image" type="file" accept="image/*"  @change="onImageChange">
        <img v-if="imagePreview" class="img-preview" id="image-preview" :src="imagePreview" alt="Превью">
        <p v-if="errors.image" class="error-text">{{errors.image}}</p>
      </div>
      <fieldset class="field">
        <legend>Ингредиенты</legend>
        <div class="check-grid">
          <label class="check" v-for="ing in ingredients" :key="ing.id">
            <input type="checkbox"  :value="ing.id" v-model="form.ingredients">{{ing.name}}
          </label>
      </div>
        <p v-if="errors.ingredients" class="error-text">{{errors.ingredients}}</p>
      </fieldset>
      <div class="form-actions">
        <button class="btn" type="submit">Опубликовать</button>
        <RouterLink class="btn btn-light" to="/">Отмена</RouterLink>
      </div>
    </form>
  </div>
</template>

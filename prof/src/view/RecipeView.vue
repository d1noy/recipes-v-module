<script setup>
import {ref, onMounted} from 'vue'
import {useRoute} from 'vue-router'
import api from "@/api.js";

const route = useRoute();
const recipe = ref(null);

onMounted(async () => {
  const res = await api.get(`/recipes/${route.params.id}`);
  recipe.value = res.data.data ?? res.data;
});
</script>

<template>
  <div class="container">
    <p><RouterLink to="/">← Ко всем рецептам</RouterLink></p>
    <p v-if="!recipe" class="empty">Загрузка…</p>
    <div class="recipe-view" v-else>
      <img :src="recipe.image" :alt="recipe.title">
      <div>
        <h1>{{recipe.title}}</h1>
        <div class="meta"><span class="chip">🔥 {{recipe.calories}}</span><span class="chip">⏱ {{recipe.cooking_time}}</span></div>
        <p>{{ recipe.description }}</p>
        <h3>Ингредиенты</h3>
        <ul class="ing-list"><li v-for="ing in recipe.ingredients" :key="ing.id">{{ing.name}}</li></ul>
      </div>
    </div>
  </div>
</template>


<script setup>

import { RouterLink }from "vue-router";
import {ref, computed, onMounted} from "vue";
import api from "@/api.js";


const recipes = ref([]);
const sortBy = ref("date");
const dir = ref('desc');



const sorted = computed(() => {
  return [...recipes.value].sort((a,b) => {
    if(sortBy.value === "cooking_time") return dir.value === 'asc' ? a.cooking_time - b.cooking_time : b.cooking_time - a.cooking_time;
    if(sortBy.value === "calories") return dir.value === 'asc' ? a.calories - b.calories : b.calories - a.calories;
    return dir.value === 'asc'
        ? new Date(a.created_at) - new Date(b.created_at)
        : new Date(b.created_at) - new Date(a.created_at);
  })
})

onMounted(async () => {
  const res = await api.get("/recipes");
  recipes.value = res.data.data ?? res.data;
})

</script>

<template>
  <main class="page">
    <div class="container">
      <div class="page-head">
        <h1>Все рецепты</h1>
        <div class="sortbar">
          <label for="sort-by">Сортировка:</label>
          <select id="sort-by" v-model="sortBy">
            <option value="date">По дате создания</option>
            <option value="cooking_time">По времени приготовления</option>
            <option value="calories">По калорийности</option>
          </select>
          <button class="btn btn-light" id="sort-dir" type="button" @click="dir = dir === 'asc' ? 'desc' : 'asc'" >{{dir === 'asc' ? '↑ По возрастанию' : '↓ По убыванию'}}</button>
        </div>
      </div>
      <!-- По умолчанию: новые сверху -->
      <div class="grid" id="recipes">
        <article class="recipe-card" v-for="r in sorted" :key="r.id">
          <RouterLink :to="`/recipe/${r.id}`"><img :src="r.image" :alt="r.title"></RouterLink>
          <div class="body">
            <h3><RouterLink :to="`/recipe/${r.id}`">{{ r.title }}</RouterLink></h3>
            <p>{{r.description}}.</p>
            <div class="meta"><span class="chip">🔥 {{ r.calories }}</span><span class="chip">⏱ {{ r.cooking_time }}</span></div>
          </div>

        </article>
      </div>
      <p v-if="!sorted.length" class="empty">Рецептов пока нет</p>
    </div>
  </main>
</template>


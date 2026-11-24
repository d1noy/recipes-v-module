<script setup>

import { onMounted, ref } from 'vue'
import {useRouter} from "vue-router";
import api from "@/api.js";

const router = useRouter();
const recipes = ref([])
const modalVisible = ref(false);
const deleting = ref(null);

function askDelete(r){
  deleting.value = r;
  modalVisible.value = true;
}

async function confirmDelete(){
  await api.delete(`/recipes/${deleting.value.id}`);
  recipes.value = recipes.value.filter(item => item.id !== deleting.value.id);
  modalVisible.value = false;
}

onMounted(async () => {
  const res = await api.get("/my/recipes");
  recipes.value = res.data.data ?? res.data;
})

async function del(id) {
  if(!confirm("Удалить рецепт?")) return;
  await api.delete(`/recipes/${id}`);
  recipes.value = recipes.value.filter(item => item.id !== id);
}

</script>

<template>
  <div class="container">
    <div class="page-head">
      <h1>Мои рецепты</h1>
    </div>
    <p v-if="!recipes.length" class="empty">Рецептов пока нет</p>
    <div class="grid" v-else>
      <article class="recipe-card" v-for="r in recipes" :key="r.id">
        <RouterLink :to="`/recipe/${r.id}`"><img :src="r.image" :alt="r.title"></RouterLink>
        <div class="body">
          <h3><RouterLink :to="`/recipe/${r.id}`">{{ r.title }}</RouterLink></h3>
          <p>{{ r.description }}</p>
          <div class="meta"><span class="chip">🔥 {{ r.calories }}</span></div>
        </div>
        <div class="card-actions">
          <RouterLink class="btn btn-light btn-sm" :to="`/recipe/${r.id}/edit`">✏️ Изменить</RouterLink>
          <button class="btn btn-danger btn-sm" type="button" @click="del(r.id)">🗑 Удалить</button>
        </div>
      </article>

    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal" id="delete-modal" v-show="modalVisible">
      <div class="modal-box" role="dialog" aria-modal="true">
        <h2>Удалить рецепт?</h2>
        <p>Рецепт «<strong> {{deleting?.title}}></strong>» будет удалён без возможности восстановления.</p>
        <div class="modal-actions">
          <button class="btn btn-light" type="button" @click="modalVisible = false" data-close>Отмена</button>
          <button class="btn btn-danger-solid" type="button" @click="confirmDelete">Удалить</button>
        </div>
      </div>
    </div>
  </div>
</template>


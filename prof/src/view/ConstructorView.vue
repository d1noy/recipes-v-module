<script setup>
import { ref, computed, onMounted } from 'vue'
import api from "@/api.js";

const ingredients = ref([])
const recipes = ref([])
const selected = ref([])

onMounted(async () => {
  try {
    const [ingRes, recRes] = await Promise.all([
      api.get('/ingredients'),
      api.get('/recipes'),
    ])

    ingredients.value = ingRes.data.data ?? ingRes.data

    recipes.value = (recRes.data.data ?? recRes.data).map(r => {
      let ids = []

      if (Array.isArray(r.ingredients)) {
        ids = r.ingredients.map(i => i.id)
      } else if (typeof r.ingredientsIds === 'string') {
        ids = r.ingredientsIds.split(',').map(Number)
      } else if (Array.isArray(r.ingredientsIds)) {
        ids = r.ingredientsIds
      }

      return { ...r, ingredientsIds: ids }
    })
  } catch (error) {
    console.error('Ошибка загрузки данных конструктора:', error)
  }
})

const toggleIngredient = (id) => {
  if (selected.value.includes(id)) {
    selected.value = selected.value.filter(i => i !== id)
  } else {
    selected.value.push(id)
  }
}

let draggingId = null
const onDragStart = (id) => { draggingId = id }

const onDrop = () => {
  if (draggingId && !selected.value.includes(draggingId)) {
    selected.value.push(draggingId)
  }
  draggingId = null
}

const onRemove = (id) => {
  selected.value = selected.value.filter(i => i !== id)
}

const filteredRecipes = computed(() => {
  if (!selected.value.length) return []
  return recipes.value.filter(r =>
      selected.value.every(id => r.ingredientsIds.includes(id))
  )
})
</script>



<template>
  <div class="container">
    <div class="page-head"><h1>Конструктор рецептов</h1></div>
    <div class="builder">
      <section class="panel">
        <h2>Ингредиенты</h2>
        <p class="muted" style="margin:0 0 12px;font-size:14px">Перетащите нужные в область справа (на телефоне — двойной тап)</p>

        <div class="ing-pool" id="pool">
          <span
              v-for="ing in ingredients"
              :key="ing.id"
              class="ing"
              draggable="true"
              @dragstart="onDragStart(ing.id)"
              @click="toggleIngredient(ing.id)"
          >
            {{ ing.name }}
          </span>
        </div>
      </section>

      <section class="panel">
        <h2>Ваши ингредиенты</h2>
        <div
            @dragover.prevent
            @drop.prevent="onDrop"
            class="dropzone"
            id="dropzone"
        >
          <span v-if="!selected.length" class="placeholder">Перетащите ингредиенты сюда</span>
          <template v-else>
            <span
                v-for="id in selected"
                :key="id"
                class="ing chosen"
                @click.stop="onRemove(id)"
            >
              {{ ingredients.find(i => i.id === id)?.name }} <b>✕</b>
            </span>
          </template>
        </div>
      </section>
    </div>

    <h2 id="results-count" style="margin-bottom:16px;font-size:22px">
      {{
        !selected.length
            ? 'Добавьте ингредиенты, чтобы увидеть рецепты'
            : `Найдено: ${filteredRecipes.length}`
      }}
    </h2>

    <div class="grid" id="results">
      <article v-for="r in filteredRecipes" :key="r.id" class="recipe-card">
        <RouterLink :to="`/recipe/${r.id}`" class="card-link">
          <img :src="r.image" :alt="r.title">
        </RouterLink>

        <div class="body">
          <h3>
            <RouterLink :to="`/recipe/${r.id}`">{{ r.title }}</RouterLink>
          </h3>
          <p>{{ r.description }}</p>
          <div class="meta">
            <span class="chip">🔥 {{ r.calories }} ккал</span>
            <span class="chip">⏱ {{ r.cooking_time }} мин</span>
          </div>
        </div>
      </article>

      <p v-if="selected.length && !filteredRecipes.length" class="empty" id="results-empty">
        Рецептов с такими ингредиентами не найдено
      </p>
    </div>
  </div>
</template>

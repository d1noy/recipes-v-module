import './assets/main.css'
import { createApp } from 'vue'
import App from './App.vue'
import {createRouter, createWebHistory} from "vue-router";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: () => import('./view/HomeView.vue')},
        { path: '/login', component: () => import('./view/LoginView.vue')},
        { path: '/register', component: () => import('./view/RegisterView.vue')},
        { path: '/recipe/create', component: () => import('./view/RecipeCreateView.vue')},
        { path: '/my/recipes', component: () => import('./view/MyRecipeView.vue')},
        { path: '/recipe/:id', component: () => import('./view/RecipeView.vue')},
        { path: '/recipe/:id/edit', component: () => import('./view/RecipeEditView.vue')},
        { path: '/constructor', component: () => import('./view/ConstructorView.vue')},



    ]
})

router.beforeEach((to) => {
    const isAuth = localStorage.getItem('token')

    if(to.path === '/' && !isAuth) {
        return '/login'
    }
    if((to.path === '/login' || to.path === '/register') && isAuth) {
        return '/'
    }

})

createApp(App).use(router).mount('#app')

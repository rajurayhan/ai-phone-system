import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';

// Import components
import Login from './components/auth/Login.vue';
import Register from './components/auth/Register.vue';
import Dashboard from './components/dashboard/Dashboard.vue';
import Profile from './components/profile/Profile.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';
import AdminUsers from './components/admin/Users.vue';
import AdminAssistants from './components/admin/Assistants.vue';
import LandingPage from './components/landing/LandingPage.vue';
import AssistantForm from './components/assistant/AssistantForm.vue';
import UserAssistants from './components/dashboard/UserAssistants.vue';
import Pricing from './components/pricing/Pricing.vue';
import SubscriptionManager from './components/subscription/SubscriptionManager.vue';
import TransactionHistory from './components/transactions/TransactionHistory.vue';
import PaymentForm from './components/transactions/PaymentForm.vue';
import TransactionManagement from './components/admin/TransactionManagement.vue';

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'landing',
            component: LandingPage,
            meta: { requiresGuest: true }
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
            meta: { requiresGuest: true }
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
            meta: { requiresGuest: true }
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: Dashboard,
            meta: { requiresAuth: true }
        },
        {
            path: '/profile',
            name: 'profile',
            component: Profile,
            meta: { requiresAuth: true }
        },
        {
            path: '/admin',
            name: 'admin',
            component: AdminDashboard,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/users',
            name: 'admin-users',
            component: AdminUsers,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/assistants',
            name: 'admin-assistants',
            component: AdminAssistants,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/transactions',
            name: 'admin-transactions',
            component: TransactionManagement,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/assistants',
            name: 'user-assistants',
            component: UserAssistants,
            meta: { requiresAuth: true }
        },
        {
            path: '/assistants/create',
            name: 'assistant-create',
            component: AssistantForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/assistants/:id/edit',
            name: 'assistant-edit',
            component: AssistantForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/pricing',
            name: 'pricing',
            component: Pricing,
            meta: { requiresAuth: true }
        },
        {
            path: '/subscription',
            name: 'subscription',
            component: SubscriptionManager,
            meta: { requiresAuth: true }
        },
        {
            path: '/transactions',
            name: 'transaction-history',
            component: TransactionHistory,
            meta: { requiresAuth: true }
        },
        {
            path: '/payment',
            name: 'payment',
            component: PaymentForm,
            meta: { requiresAuth: true }
        }
    ]
});

// Navigation guard
router.beforeEach((to, from, next) => {
    const isAuthenticated = !!localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    const isAdmin = user.role === 'admin';

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.requiresGuest && isAuthenticated) {
        next('/dashboard');
    } else if (to.meta.requiresAdmin && !isAdmin) {
        next('/dashboard');
    } else {
        next();
    }
});

// Create Vue app
const app = createApp(App);
app.use(router);
app.mount('#app'); 
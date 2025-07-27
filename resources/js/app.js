import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { updateDocumentTitle } from './utils/systemSettings.js'
import App from './App.vue';

// Import components
import Login from './components/auth/Login.vue';
import Register from './components/auth/Register.vue';
import Dashboard from './components/dashboard/Dashboard.vue';
import Profile from './components/profile/Profile.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';
import AdminUsers from './components/admin/Users.vue';
import AdminAssistants from './components/admin/Assistants.vue';
import AdminFeatures from './components/admin/Features.vue';
import AdminPackages from './components/admin/Packages.vue';
import AdminSubscriptions from './components/admin/Subscriptions.vue';
import Templates from './components/admin/Templates.vue';
import LandingPage from './components/landing/LandingPage.vue';
import AssistantForm from './components/assistant/AssistantForm.vue';
import UserAssistants from './components/dashboard/UserAssistants.vue';
import Pricing from './components/pricing/Pricing.vue';
import SubscriptionManager from './components/subscription/SubscriptionManager.vue';
import TransactionHistory from './components/transactions/TransactionHistory.vue';
import PaymentForm from './components/transactions/PaymentForm.vue';
import TransactionManagement from './components/admin/TransactionManagement.vue';
import DemoRequestForm from './components/demo/DemoRequestForm.vue'
import DemoRequests from './components/admin/DemoRequests.vue'
import SystemSettings from './components/admin/SystemSettings.vue'
import NotFound from './components/errors/NotFound.vue'
import ErrorPage from './components/errors/ErrorPage.vue'

// Set initial document title
updateDocumentTitle('XpartFone - Revolutionary Voice AI Platform')

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'landing',
            component: LandingPage
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
            path: '/admin/features',
            name: 'admin-features',
            component: AdminFeatures,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/packages',
            name: 'admin-packages',
            component: AdminPackages,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/subscriptions',
            name: 'admin-subscriptions',
            component: AdminSubscriptions,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/templates',
            name: 'admin-templates',
            component: Templates,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/demo-requests',
            name: 'admin-demo-requests',
            component: DemoRequests,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/system-settings',
            name: 'admin-system-settings',
            component: SystemSettings,
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
            component: Pricing
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
        },
        {
            path: '/demo-request',
            name: 'demo-request',
            component: DemoRequestForm,
            meta: { requiresAuth: true }
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFound
        },
        {
            path: '/test-error',
            name: 'test-error',
            component: ErrorPage,
            props: {
                errorCode: '500',
                errorTitle: 'Test Error',
                errorMessage: 'This is a test error page.',
                errorDescription: 'This page is for testing error handling.'
            }
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

// Error handling for navigation
router.onError((error) => {
    console.error('Navigation error:', error);
    // Redirect to 404 page for navigation errors
    router.push('/404');
});

// Create Vue app
const app = createApp(App);
app.use(router);

// Global error handler
app.config.errorHandler = (err, vm, info) => {
    console.error('Vue error:', err);
    console.error('Error info:', info);
    // You could redirect to an error page here if needed
};

// Global unhandled promise rejection handler
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    event.preventDefault();
});

app.mount('#app'); 
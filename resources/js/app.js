import './bootstrap';
import { createApp, h } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { updateDocumentTitle } from './utils/systemSettings.js'
import App from './App.vue';

// Import components
import Login from './components/auth/Login.vue';
import Register from './components/auth/Register.vue';
import ForgotPassword from './components/auth/ForgotPassword.vue';
import ResetPassword from './components/auth/ResetPassword.vue';
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
import CallLogsPage from './components/call-logs/CallLogsPage.vue'
import CallLogDetailsPage from './components/call-logs/CallLogDetailsPage.vue'
import AdminCallLogDetailsPage from './components/admin/AdminCallLogDetailsPage.vue'
import AdminCallLogs from './components/admin/CallLogs.vue'
import ContactManagement from './components/admin/ContactManagement.vue'
import TermsOfService from './components/shared/TermsOfService.vue'
import PrivacyPolicy from './components/shared/PrivacyPolicy.vue'
import NotFound from './components/shared/NotFound.vue'

// Set initial document title
updateDocumentTitle()

// Create a conditional dashboard component
const ConditionalDashboard = {
    name: 'ConditionalDashboard',
    components: {
        Dashboard,
        AdminDashboard
    },
    computed: {
        isAdmin() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            return user.role === 'admin';
        }
    },
    template: `
        <div>
            <AdminDashboard v-if="isAdmin" />
            <Dashboard v-else />
        </div>
    `
};

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
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPassword,
            meta: { requiresGuest: true }
        },
        {
            path: '/password-reset/:token',
            name: 'reset-password',
            component: ResetPassword,
            meta: { requiresGuest: true }
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: ConditionalDashboard,
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
            path: '/call-logs',
            name: 'call-logs',
            component: CallLogsPage,
            meta: { requiresAuth: true }
        },
        {
            path: '/call-logs/:call_id',
            name: 'call-log-details',
            component: CallLogDetailsPage,
            meta: { requiresAuth: true }
        },
        {
            path: '/admin/call-logs',
            name: 'admin-call-logs',
            component: AdminCallLogs,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/call-logs/:call_id',
            name: 'admin-call-log-details',
            component: AdminCallLogDetailsPage,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/admin/contacts',
            name: 'admin-contacts',
            component: ContactManagement,
            meta: { requiresAuth: true, requiresAdmin: true }
        },
        {
            path: '/terms',
            name: 'terms',
            component: TermsOfService
        },
        {
            path: '/privacy',
            name: 'privacy',
            component: PrivacyPolicy
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFound
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

// Global afterEach hook to update document titles
router.afterEach((to) => {
    // Map route names to page titles
    const pageTitles = {
        'landing': 'Home',
        'login': 'Sign In',
        'register': 'Sign Up',
        'forgot-password': 'Forgot Password',
        'reset-password': 'Reset Password',
        'dashboard': 'Dashboard',
        'admin': 'Admin Dashboard',
        'admin-users': 'User Management',
        'admin-assistants': 'Assistant Management',
        'admin-transactions': 'Transaction Management',
        'admin-features': 'Feature Management',
        'admin-packages': 'Package Management',
        'admin-subscriptions': 'Subscription Management',
        'admin-templates': 'Template Management',
        'admin-demo-requests': 'Demo Requests',
        'admin-system-settings': 'System Settings',
        'admin-call-logs': 'Call Logs',
        'admin-call-log-details': 'Call Details',
        'admin-contacts': 'Contact Management',
        'user-assistants': 'My Assistants',
        'assistant-create': 'Create Assistant',
        'assistant-edit': 'Edit Assistant',
        'profile': 'Profile',
        'pricing': 'Pricing',
        'subscription': 'Subscription',
        'transaction-history': 'Transaction History',
        'payment': 'Payment',
        'demo-request': 'Request Demo',
        'call-logs': 'Call Logs',
        'call-log-details': 'Call Details',
        'terms': 'Terms of Service',
        'privacy': 'Privacy Policy',
        'not-found': 'Page Not Found'
    };

    const pageTitle = pageTitles[to.name] || to.name;
    updateDocumentTitle(pageTitle);
});

// Create Vue app
const app = createApp(App);
app.use(router);
app.mount('#app'); 
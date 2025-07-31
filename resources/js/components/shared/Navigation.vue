<template>
  <nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <router-link to="/dashboard" class="flex items-center hover:opacity-80 transition-opacity">
              <div v-if="settings.logo_url" class="h-8 w-auto">
                <img :src="settings.logo_url" :alt="settings.site_title" class="h-full w-auto">
              </div>
              <div v-else class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </div>
              <div class="ml-2">
                <h1 class="text-xl font-bold text-gray-900">{{ settings.site_title || 'XpartFone' }}</h1>
              </div>
            </router-link>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <router-link 
              to="/dashboard" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/dashboard' 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Dashboard
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/assistants" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/assistants' 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              My Assistants
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/demo-request" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/demo-request' 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Request Demo
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/call-logs" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/call-logs' 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Call Logs
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/subscription" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/subscription' 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Subscription
            </router-link>
            <router-link 
              v-if="!isAdmin"
              to="/transactions" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/transactions' 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Transactions
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/users" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/users') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Users
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/assistants" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/assistants') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              All Assistants
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/features" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/features') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Features
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/packages" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/packages') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Packages
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/subscriptions" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/subscriptions') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Subscriptions
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/templates" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/templates') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Templates
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/demo-requests" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/demo-requests') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Demo Requests
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/call-logs" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/call-logs') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Call Logs
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/contacts" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/contacts') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Contact Management
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/transactions" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/transactions') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Transactions
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/system-settings" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/system-settings') 
                  ? 'border-primary-500 text-primary-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              System Settings
            </router-link>
          </div>
        </div>
        <div class="flex items-center">
          <div class="flex items-center space-x-4">
            <router-link to="/profile" class="text-gray-500 hover:text-gray-700">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </router-link>
            <div class="relative">
              <button @click="showUserMenu = !showUserMenu" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <div class="h-8 w-8 rounded-full bg-primary-600 flex items-center justify-center">
                  <span class="text-white text-sm font-medium">{{ userInitials }}</span>
                </div>
                <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                <div class="px-4 py-2 text-sm text-gray-700 border-b border-gray-200">
                  <p class="font-medium">{{ user.name }}</p>
                  <p class="text-xs text-gray-500">{{ user.email }}</p>
                </div>
                <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</router-link>
                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { getSystemSettings } from '../../utils/systemSettings.js'
import MobileNavigation from './MobileNavigation.vue'

export default {
  name: 'Navigation',
  components: {
    MobileNavigation
  },
  data() {
    return {
      showUserMenu: false,
      user: JSON.parse(localStorage.getItem('user') || '{}'),
      settings: {
        site_title: 'XpartFone',
        logo_url: '/logo.png'
      }
    }
  },
  computed: {
    userInitials() {
      return this.user.name ? this.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    },
    isAdmin() {
      return this.user.role === 'admin';
    }
  },
  methods: {
    async loadSettings() {
      try {
        const settings = await getSystemSettings()
        this.settings = settings
        // Cache settings in localStorage for faster loading
        localStorage.setItem('settings', JSON.stringify(settings))
      } catch (error) {
        // Handle error silently
      }
    },
    async logout() {
      try {
        // Call logout API
        const response = await fetch('/api/logout', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        // Clear local storage
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        
        // Redirect to login
        this.$router.push('/login');
      } catch (error) {
        // Even if API call fails, clear local storage and redirect
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('settings');
        this.$router.push('/login');
      }
    }
  },
  mounted() {
    this.loadSettings()
    
    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.showUserMenu = false;
      }
    });
  }
}
</script> 
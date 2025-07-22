<template>
  <nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <router-link to="/dashboard" class="flex items-center hover:opacity-80 transition-opacity">
              <div class="h-8 w-8 bg-green-600 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                </svg>
              </div>
              <div class="ml-2">
                <h1 class="text-xl font-bold text-gray-900">LHG AI Voice Agent</h1>
              </div>
            </router-link>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
            <router-link 
              to="/dashboard" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/dashboard' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              Dashboard
            </router-link>
            <router-link 
              to="/assistants" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path === '/assistants' 
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              My Assistants
            </router-link>
            <router-link 
              v-if="isAdmin"
              to="/admin/users" 
              :class="[
                'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium',
                $route.path.startsWith('/admin/users') 
                  ? 'border-green-500 text-green-600' 
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
                  ? 'border-green-500 text-green-600' 
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              ]"
            >
              All Assistants
            </router-link>
          </div>
        </div>
        <div class="flex items-center">
          <div class="ml-3 relative">
            <div>
              <button @click="userMenuOpen = !userMenuOpen" class="max-w-xs bg-gray-100 flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <span class="sr-only">Open user menu</span>
                <div v-if="user.profile_picture" class="h-8 w-8 rounded-full overflow-hidden">
                  <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                </div>
                <div v-else class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center">
                  <span class="text-white font-medium">{{ userInitials }}</span>
                </div>
              </button>
            </div>
            <div v-if="userMenuOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-200 z-50">
              <div class="px-4 py-2 border-b border-gray-100">
                <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                <p class="text-xs text-gray-500">{{ user.email }}</p>
              </div>
              <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</router-link>
              <router-link to="/pricing" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pricing</router-link>
              <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navigation',
  data() {
    return {
      userMenuOpen: false,
      user: JSON.parse(localStorage.getItem('user') || '{}')
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
        
        // Redirect to login
        this.$router.push('/login');
      } catch (error) {
        console.error('Logout error:', error);
        // Even if API call fails, clear local storage and redirect
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.$router.push('/login');
      }
    }
  },
  mounted() {
    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.userMenuOpen = false;
      }
    });
  }
}
</script> 
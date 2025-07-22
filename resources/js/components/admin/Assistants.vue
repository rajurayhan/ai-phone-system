<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/admin" class="flex items-center hover:opacity-80 transition-opacity">
                <div class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
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
              <router-link to="/admin" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                Dashboard
              </router-link>
              <router-link to="/admin/users" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                Users
              </router-link>
              <router-link to="/admin/assistants" class="border-primary-500 text-primary-600 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                Assistants
              </router-link>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <router-link to="/dashboard" class="text-gray-700 hover:text-primary-600">User Dashboard</router-link>
            <div class="ml-3 relative">
              <div>
                <button @click="userMenuOpen = !userMenuOpen" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  <span class="sr-only">Open user menu</span>
                  <div class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center">
                    <span class="text-white font-medium">{{ userInitials }}</span>
                  </div>
                </button>
              </div>
              <div v-if="userMenuOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</router-link>
                <router-link to="/pricing" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Pricing</router-link>
                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Assistant Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage all voice assistants in the system
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button @click="createAssistant" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create Assistant
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Assistants Grid -->
        <div v-else class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="assistant in assistants" :key="assistant.id" class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                    </svg>
                  </div>
                </div>
                <div class="ml-4 flex-1">
                  <h3 class="text-lg font-medium text-gray-900">{{ assistant.name }}</h3>
                  <p class="text-sm text-gray-500">Assistant ID: {{ assistant.vapi_assistant_id }}</p>
                </div>
              </div>
              
              <div class="mt-4">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500">Owner:</span>
                  <span class="font-medium text-gray-900">{{ assistant.user?.name || 'Unknown' }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                  <span class="text-gray-500">Created:</span>
                  <span class="font-medium text-gray-900">{{ formatDate(assistant.created_at) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                  <span class="text-gray-500">Created By:</span>
                  <span class="font-medium text-gray-900">{{ assistant.creator?.name || 'Unknown' }}</span>
                </div>
              </div>

              <div class="mt-6 flex space-x-3">
                <button @click="editAssistant(assistant)" class="flex-1 bg-primary-50 border border-primary-300 text-primary-700 hover:bg-primary-100 px-3 py-2 rounded-md text-sm font-medium">
                  Edit
                </button>
                <button @click="viewStats(assistant)" class="flex-1 bg-gray-50 border border-gray-300 text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md text-sm font-medium">
                  Stats
                </button>
                <button @click="deleteAssistant(assistant.vapi_assistant_id)" class="flex-1 bg-red-50 border border-red-300 text-red-700 hover:bg-red-100 px-3 py-2 rounded-md text-sm font-medium">
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && assistants.length === 0" class="mt-8 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No assistants</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating a new voice assistant.</p>
          <div class="mt-6">
            <button @click="createAssistant" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create Assistant
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Modal -->
    <div v-if="showStatsModal" class="fixed z-10 inset-0 overflow-y-auto">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  Assistant Statistics
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                    Statistics for: {{ selectedAssistant?.name }}
                  </p>
                  <div v-if="stats" class="mt-4 space-y-3">
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-500">Total Calls:</span>
                      <span class="text-sm font-medium text-gray-900">{{ stats.totalCalls || 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-500">Successful Calls:</span>
                      <span class="text-sm font-medium text-gray-900">{{ stats.successfulCalls || 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-sm text-gray-500">Average Duration:</span>
                      <span class="text-sm font-medium text-gray-900">{{ formatDuration(stats.averageDuration) }}</span>
                    </div>
                  </div>
                  <div v-else class="mt-4 text-sm text-gray-500">
                    No statistics available for this assistant.
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button @click="closeStatsModal" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminAssistants',
  data() {
    return {
      assistants: [],
      loading: true,
      userMenuOpen: false,
      showStatsModal: false,
      selectedAssistant: null,
      stats: null
    }
  },
  computed: {
    userInitials() {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.name ? user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    }
  },
  async mounted() {
    await this.loadAssistants();
  },
  methods: {
    async loadAssistants() {
      try {
        this.loading = true;
        const response = await fetch('/api/admin/assistants', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.assistants = data.data || [];
        } else {
          console.error('Failed to load assistants');
        }
      } catch (error) {
        console.error('Error loading assistants:', error);
      } finally {
        this.loading = false;
      }
    },
    
    createAssistant() {
      // Navigate to assistant creation form
      this.$router.push('/assistants/create');
    },
    
    editAssistant(assistant) {
      // Navigate to assistant edit form
      this.$router.push(`/assistants/${assistant.vapi_assistant_id}/edit`);
    },
    
    async viewStats(assistant) {
      this.selectedAssistant = assistant;
      this.showStatsModal = true;
      
      try {
        // Fetch detailed data from Vapi API for this specific assistant
        const response = await fetch(`/api/assistants/${assistant.vapi_assistant_id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.stats = data.data.vapi_data?.stats || null;
        } else {
          this.stats = null;
        }
      } catch (error) {
        console.error('Error loading stats:', error);
        this.stats = null;
      }
    },
    
    async deleteAssistant(assistantId) {
      if (!confirm('Are you sure you want to delete this assistant?')) {
        return;
      }
      
      try {
        const response = await fetch(`/api/assistants/${assistantId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          await this.loadAssistants();
        } else {
          console.error('Failed to delete assistant');
        }
      } catch (error) {
        console.error('Error deleting assistant:', error);
      }
    },
    
    closeStatsModal() {
      this.showStatsModal = false;
      this.selectedAssistant = null;
      this.stats = null;
    },
    
    formatDuration(seconds) {
      if (!seconds) return '0s';
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = seconds % 60;
      return minutes > 0 ? `${minutes}m ${remainingSeconds}s` : `${remainingSeconds}s`;
    },

    formatDate(timestamp) {
      if (!timestamp) return 'N/A';
      const date = new Date(timestamp);
      return date.toLocaleDateString();
    },
    
    logout() {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      this.$router.push('/login');
    }
  }
}
</script> 
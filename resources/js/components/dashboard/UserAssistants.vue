<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <Navigation />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              My Voice Assistants
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your voice assistants
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button 
              @click="createAssistant" 
              :disabled="subscriptionInfo && !subscriptionInfo.hasSubscription && !isAdmin"
              class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              <span v-if="subscriptionInfo && !subscriptionInfo.hasSubscription && !isAdmin">Subscribe to Create</span>
              <span v-else>Create Assistant</span>
            </button>
          </div>
        </div>

        <!-- Subscription Status Banner -->
        <div v-if="subscriptionInfo && !subscriptionInfo.hasSubscription" class="mt-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">
                No Active Subscription
              </h3>
              <div class="mt-2 text-sm text-yellow-700">
                <p>You need an active subscription to create and manage voice assistants.</p>
              </div>
              <div class="mt-4">
                <router-link to="/subscription" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                  Subscribe Now
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Search and Sort Controls -->
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <!-- Search -->
          <div class="flex-1 max-w-sm">
            <label for="search" class="sr-only">Search assistants</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                id="search"
                v-model="searchQuery"
                @input="debounceSearch"
                type="text"
                placeholder="Search assistants by name..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm"
              />
            </div>
          </div>

          <!-- Sort -->
          <div class="flex items-center space-x-4">
            <label for="sort" class="text-sm font-medium text-gray-700">Sort by:</label>
            <select
              id="sort"
              v-model="sortBy"
              @change="loadAssistants"
              class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm rounded-md"
            >
              <option value="name">Name</option>
              <option value="created_at">Created Date</option>
            </select>
            <button
              @click="toggleSortOrder"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              <svg v-if="sortOrder === 'asc'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
              </svg>
              <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Assistants Grid -->
        <div v-else class="mt-8">
          <!-- Empty State -->
          <div v-if="assistants.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No assistants</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating your first voice assistant.</p>
            <div class="mt-6">
              <button 
                @click="createAssistant" 
                :disabled="subscriptionInfo && !subscriptionInfo.hasSubscription && !isAdmin"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span v-if="subscriptionInfo && !subscriptionInfo.hasSubscription && !isAdmin">Subscribe to Create</span>
                <span v-else>Create Assistant</span>
              </button>
            </div>
          </div>

          <!-- Assistants List -->
          <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="assistant in paginatedAssistants" :key="assistant.id" class="bg-white overflow-hidden shadow rounded-lg">
              <div class="p-6">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center">
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
                  <button @click="editAssistant(assistant)" class="flex-1 bg-green-50 border border-green-300 text-green-700 hover:bg-green-100 px-3 py-2 rounded-md text-sm font-medium">
                    Edit
                  </button>
                  <button @click="viewStats(assistant)" class="flex-1 bg-blue-50 border border-blue-300 text-blue-700 hover:bg-blue-100 px-3 py-2 rounded-md text-sm font-medium">
                    Stats
                  </button>
                  <button 
                    @click="deleteAssistant(assistant.vapi_assistant_id)" 
                    :disabled="deletingAssistant === assistant.vapi_assistant_id"
                    class="flex-1 bg-red-50 border border-red-300 text-red-700 hover:bg-red-100 px-3 py-2 rounded-md text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {{ deletingAssistant === assistant.vapi_assistant_id ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="assistants.length > 0" class="mt-8 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
              <button 
                @click="previousPage" 
                :disabled="currentPage === 1"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  currentPage === 1 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                Previous
              </button>
              <button 
                @click="nextPage" 
                :disabled="currentPage >= totalPages"
                :class="[
                  'ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md',
                  currentPage >= totalPages 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                Next
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  Showing 
                  <span class="font-medium">{{ startIndex + 1 }}</span>
                  to 
                  <span class="font-medium">{{ endIndex }}</span>
                  of 
                  <span class="font-medium">{{ assistants.length }}</span>
                  results
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                  <button 
                    @click="previousPage" 
                    :disabled="currentPage === 1"
                    :class="[
                      'relative inline-flex items-center px-2 py-2 rounded-l-md border text-sm font-medium',
                      currentPage === 1 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                        : 'bg-white text-gray-500 hover:bg-gray-50'
                    ]"
                  >
                    <span class="sr-only">Previous</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  
                  <template v-for="page in visiblePages" :key="page">
                    <button 
                      v-if="page !== '...'" 
                      @click="goToPage(page)"
                      :class="[
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                        page === currentPage 
                          ? 'z-10 bg-green-50 border-green-500 text-green-600' 
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                      ]"
                    >
                      {{ page }}
                    </button>
                    <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                      ...
                    </span>
                  </template>
                  
                  <button 
                    @click="nextPage" 
                    :disabled="currentPage >= totalPages"
                    :class="[
                      'relative inline-flex items-center px-2 py-2 rounded-r-md border text-sm font-medium',
                      currentPage >= totalPages 
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                        : 'bg-white text-gray-500 hover:bg-gray-50'
                    ]"
                  >
                    <span class="sr-only">Next</span>
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Modal -->
    <div v-if="showStatsModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
          <div>
            <div class="mt-3 text-center sm:mt-5">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                Statistics for {{ selectedAssistant?.name }}
              </h3>
              <div class="mt-2">
                <div v-if="stats" class="space-y-4">
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Total Calls:</span>
                    <span class="text-sm text-gray-900">{{ stats.totalCalls || 0 }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Successful Calls:</span>
                    <span class="text-sm text-gray-900">{{ stats.successfulCalls || 0 }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Average Duration:</span>
                    <span class="text-sm text-gray-900">{{ stats.averageDuration || 0 }}s</span>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">
                  No statistics available for this assistant.
                </div>
              </div>
            </div>
          </div>
          <div class="mt-5 sm:mt-6">
            <button @click="closeStatsModal" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:text-sm">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import { showDeleteConfirm, showSuccess, showError } from '../../utils/sweetalert.js'
import axios from 'axios'

export default {
  name: 'UserAssistants',
  components: {
    Navigation
  },
  data() {
    return {
      loading: true,
      assistants: [],
      currentPage: 1,
      itemsPerPage: 9,
      showStatsModal: false,
      selectedAssistant: null,
      stats: null,
      searchQuery: '',
      sortBy: 'name',
      sortOrder: 'asc',
      searchTimeout: null,
      deletingAssistant: null, // New state for tracking deletion
      subscriptionInfo: null // New state for subscription info
    }
  },
  computed: {
    totalPages() {
      return Math.ceil(this.assistants.length / this.itemsPerPage);
    },
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage;
    },
    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.assistants.length);
    },
    paginatedAssistants() {
      return this.assistants.slice(this.startIndex, this.endIndex);
    },
    visiblePages() {
      const pages = [];
      const totalPages = this.totalPages;
      const current = this.currentPage;
      
      if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) {
          pages.push(i);
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(totalPages);
        } else if (current >= totalPages - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = totalPages - 4; i <= totalPages; i++) {
            pages.push(i);
          }
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(totalPages);
        }
      }
      
      return pages;
    },
    isAdmin() {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.role === 'admin';
    }
  },
  async mounted() {
    await this.loadAssistants();
    await this.loadSubscriptionInfo(); // Load subscription info on mount
  },
  methods: {
    async loadAssistants() {
      try {
        this.loading = true;
        const params = {
          sort_by: this.sortBy,
          sort_order: this.sortOrder,
          search: this.searchQuery
        };

        const response = await axios.get('/api/assistants', { params });
        this.assistants = response.data.data || [];
      } catch (error) {
        console.error('Error loading assistants:', error);
        if (error.response && error.response.status === 401) {
          this.$router.push('/login');
        }
      } finally {
        this.loading = false;
      }
    },
    
    createAssistant() {
      this.$router.push('/assistants/create');
    },
    
    editAssistant(assistant) {
      this.$router.push(`/assistants/${assistant.vapi_assistant_id}/edit`);
    },
    
    async viewStats(assistant) {
      this.selectedAssistant = assistant;
      this.showStatsModal = true;
      
      try {
        const response = await axios.get(`/api/assistants/${assistant.vapi_assistant_id}`);
        this.stats = response.data.data.vapi_data?.stats || null;
      } catch (error) {
        console.error('Error loading stats:', error);
        this.stats = null;
      }
    },
    
    async deleteAssistant(assistantId) {
      const result = await showDeleteConfirm(
        'Delete Assistant',
        'Are you sure you want to delete this assistant? This action cannot be undone.'
      );
      
      if (!result.isConfirmed) {
        return;
      }
      
      try {
        this.deletingAssistant = assistantId; // Set loading state
        console.log('Deleting assistant:', assistantId);
        const response = await axios.delete(`/api/assistants/${assistantId}`);
        
        console.log('Delete response status:', response.status);
        
        if (response.status === 200) {
            const result = response.data;
            console.log('Delete successful:', result);
            await this.loadAssistants();
        } else {
          const errorData = response.data;
          console.error('Failed to delete assistant:', errorData);
          await showError('Delete Failed', errorData.message || 'Failed to delete assistant. Please try again.');
        }
      } catch (error) {
        console.error('Error deleting assistant:', error);
        await showError('Error', 'Failed to delete assistant: ' + error.message);
      } finally {
        this.deletingAssistant = null; // Reset loading state
      }
    },
    
    closeStatsModal() {
      this.showStatsModal = false;
      this.selectedAssistant = null;
      this.stats = null;
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    
    previousPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },
    
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    },
    
    goToPage(page) {
      this.currentPage = page;
    },

    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1; // Reset to first page when search changes
        this.loadAssistants();
      }, 500);
    },

    toggleSortOrder() {
      this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      this.currentPage = 1; // Reset to first page when sort order changes
      this.loadAssistants();
    },

    async loadSubscriptionInfo() {
      try {
        // Don't load subscription info for admin users
        if (this.isAdmin) {
          this.subscriptionInfo = null;
          return;
        }
        
        const response = await axios.get('/api/subscriptions/usage');
        const usage = response.data.data;
        
        if (usage && usage.package) {
          this.subscriptionInfo = {
            hasSubscription: true,
            plan: usage.package.name || 'No Plan',
            used: usage.assistants.used || 0,
            limit: usage.assistants.limit || 0
          };
        } else {
          this.subscriptionInfo = {
            hasSubscription: false,
            plan: 'No Plan',
            used: 0,
            limit: 0
          };
        }
      } catch (error) {
        console.error('Error loading subscription info:', error);
        // If 404, it means no active subscription
        if (error.response && error.response.status === 404) {
          this.subscriptionInfo = {
            hasSubscription: false,
            plan: 'No Plan',
            used: 0,
            limit: 0
          };
        } else {
          this.subscriptionInfo = {
            hasSubscription: false,
            plan: 'Unknown',
            used: 0,
            limit: 0
          };
        }
      }
    }
  }
}
</script> 
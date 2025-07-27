<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <Navigation />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Active Voice Agents</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.activeAgents }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Conversations</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.totalConversations }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Success Rate</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.successRate }}%</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Avg Response Time</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ stats.avgResponseTime }}s</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8">
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
              <div class="mt-5">
                <div class="flow-root">
                  <ul class="-mb-8">
                    <li v-for="activity in recentActivity" :key="activity.id">
                      <div class="relative pb-8">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                        <div class="relative flex space-x-3">
                          <div>
                            <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                              <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                              </svg>
                            </span>
                          </div>
                          <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                            <div>
                              <p class="text-sm text-gray-500">{{ activity.description }}</p>
                            </div>
                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                              <time :datetime="activity.time">{{ formatTime(activity.time) }}</time>
                            </div>
                          </div>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'

export default {
  name: 'Dashboard',
  components: {
    Navigation
  },
  data() {
    return {
      stats: {
        activeAgents: 0,
        totalConversations: 0,
        successRate: 0,
        avgResponseTime: 0
      },
      recentActivity: []
    }
  },
  async mounted() {
    await this.loadStats();
    await this.loadRecentActivity();
  },
  methods: {
    async loadStats() {
      try {
        const response = await fetch('/api/dashboard/stats', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.stats = data.data;
        } else {
          console.error('Failed to load stats');
        }
      } catch (error) {
        console.error('Error loading stats:', error);
      }
    },
    
    async loadRecentActivity() {
      try {
        const response = await fetch('/api/dashboard/activity', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.recentActivity = data.data;
        } else {
          console.error('Failed to load activity');
        }
      } catch (error) {
        console.error('Error loading activity:', error);
      }
    },
    
    formatTime(time) {
      return new Date(time).toLocaleString();
    }
  }
}
</script> 
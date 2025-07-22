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
              User Management
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage users and their permissions
            </p>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4">
            <button @click="createUser" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
              <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add User
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-8 flex justify-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <!-- Users Table -->
        <div v-else class="mt-8">
          <!-- Search -->
          <div class="mb-4">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search users..."
              class="w-full sm:w-64 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
            />
          </div>

          <!-- Users List -->
          <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
              <li v-for="user in filteredUsers" :key="user.id" class="px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div v-if="user.profile_picture" class="h-10 w-10 rounded-full overflow-hidden">
                        <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                      </div>
                      <div v-else class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center">
                        <span class="text-white font-medium">{{ getUserInitials(user.name) }}</span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                      <div class="text-sm text-gray-500">{{ user.email }}</div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'
                      ]">
                        {{ user.role }}
                      </span>
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]">
                        {{ user.status }}
                      </span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <button @click="editUser(user)" class="text-green-600 hover:text-green-900 text-sm font-medium">
                        Edit
                      </button>
                      <button @click="toggleUserStatus(user)" :class="user.status === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'" class="text-sm font-medium">
                        {{ user.status === 'active' ? 'Suspend' : 'Activate' }}
                      </button>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>

          <!-- Empty State -->
          <div v-if="filteredUsers.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
            <p class="mt-1 text-sm text-gray-500">No users match your search criteria.</p>
          </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <form @submit.prevent="submitCreateUser">
                <div class="space-y-4">
                  <div>
                    <label for="create-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                      id="create-name"
                      v-model="createForm.name"
                      type="text"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="create-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                      id="create-email"
                      v-model="createForm.email"
                      type="email"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="create-password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input
                      id="create-password"
                      v-model="createForm.password"
                      type="password"
                      required
                      minlength="8"
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="create-role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select
                      id="create-role"
                      v-model="createForm.role"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                  
                  <div>
                    <label for="create-status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      id="create-status"
                      v-model="createForm.status"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="closeCreateModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="createLoading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    {{ createLoading ? 'Creating...' : 'Create User' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
          <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit User</h3>
                <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              <form @submit.prevent="submitEditUser">
                <div class="space-y-4">
                  <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                      id="edit-name"
                      v-model="editForm.name"
                      type="text"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="edit-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                      id="edit-email"
                      v-model="editForm.email"
                      type="email"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    />
                  </div>
                  
                  <div>
                    <label for="edit-role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select
                      id="edit-role"
                      v-model="editForm.role"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </div>
                  
                  <div>
                    <label for="edit-status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                      id="edit-status"
                      v-model="editForm.status"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    >
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="closeEditModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="editLoading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    {{ editLoading ? 'Updating...' : 'Update User' }}
                  </button>
                </div>
              </form>
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
  name: 'AdminUsers',
  components: {
    Navigation
  },
  data() {
    return {
      loading: true,
      users: [],
      searchQuery: '',
      showCreateModal: false,
      showEditModal: false,
      createLoading: false,
      editLoading: false,
      createForm: {
        name: '',
        email: '',
        password: '',
        role: 'user',
        status: 'active'
      },
      editForm: {
        id: null,
        name: '',
        email: '',
        role: 'user',
        status: 'active'
      }
    }
  },
  computed: {
    filteredUsers() {
      if (!this.searchQuery) return this.users;
      const query = this.searchQuery.toLowerCase();
      return this.users.filter(user => 
        user.name.toLowerCase().includes(query) || 
        user.email.toLowerCase().includes(query)
      );
    }
  },
  async mounted() {
    await this.loadUsers();
  },
  methods: {
    async loadUsers() {
      try {
        this.loading = true;
        const response = await fetch('/api/admin/users', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          this.users = data.data || [];
        } else {
          console.error('Failed to load users');
        }
      } catch (error) {
        console.error('Error loading users:', error);
      } finally {
        this.loading = false;
      }
    },
    
    getUserInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    },
    
    createUser() {
      this.showCreateModal = true;
      this.createForm = {
        name: '',
        email: '',
        password: '',
        role: 'user',
        status: 'active'
      };
      this.createLoading = false;
    },
    
    closeCreateModal() {
      this.showCreateModal = false;
    },

    async submitCreateUser() {
      if (!this.createForm.name || !this.createForm.email || !this.createForm.password) {
        alert('Name, Email, and Password are required.');
        return;
      }

      try {
        this.createLoading = true;
        const response = await fetch('/api/admin/users', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.createForm)
        });

        if (response.ok) {
          const data = await response.json();
          this.users.push(data.data);
          this.closeCreateModal();
          alert('User created successfully!');
        } else {
          const errorData = await response.json();
          alert(`Failed to create user: ${errorData.message || 'Unknown error'}`);
        }
      } catch (error) {
        console.error('Error submitting create user:', error);
        alert('An error occurred while creating the user.');
      } finally {
        this.createLoading = false;
      }
    },

    editUser(user) {
      this.editForm = { ...user };
      this.showEditModal = true;
      this.editLoading = false;
    },

    closeEditModal() {
      this.showEditModal = false;
    },

    async submitEditUser() {
      if (!this.editForm.name || !this.editForm.email) {
        alert('Name and Email are required for editing.');
        return;
      }

      try {
        this.editLoading = true;
        const response = await fetch(`/api/admin/users/${this.editForm.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(this.editForm)
        });

        if (response.ok) {
          const data = await response.json();
          const index = this.users.findIndex(user => user.id === this.editForm.id);
          if (index !== -1) {
            this.users[index] = data.data;
          }
          this.closeEditModal();
          alert('User updated successfully!');
        } else {
          const errorData = await response.json();
          alert(`Failed to update user: ${errorData.message || 'Unknown error'}`);
        }
      } catch (error) {
        console.error('Error submitting edit user:', error);
        alert('An error occurred while updating the user.');
      } finally {
        this.editLoading = false;
      }
    },
    
    async toggleUserStatus(user) {
      if (!confirm(`Are you sure you want to ${user.status === 'active' ? 'suspend' : 'activate'} this user?`)) {
        return;
      }
      
      try {
        const response = await fetch(`/api/admin/users/${user.id}/toggle-status`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          await this.loadUsers();
        } else {
          console.error('Failed to toggle user status');
        }
      } catch (error) {
        console.error('Error toggling user status:', error);
      }
    }
  }
}
</script> 
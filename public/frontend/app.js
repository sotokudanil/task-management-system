let currentUser = null;
const API_BASE_URL = 'http://localhost:8000/api';

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on login page or dashboard
    if (document.getElementById('loginForm')) {
        setupLoginPage();
    } else {
        checkAuth();
    }

    //Setup save button
    document.getElementById('saveTaskBtn').addEventListener('click', function (e) {
        e.preventDefault();
        saveTask();
    });
});

let currentEditingTaskId = null;

function setupLoginPage() {
    const loginForm = document.getElementById('loginForm');
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        try {
            const response = await fetch(`${API_BASE_URL}/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (response.ok) {
                localStorage.setItem('authToken', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                window.location.href = 'index.html';
            } else {
                alert('Login failed: ' + (data.message || 'Invalid credentials'));
            }
        } catch (error) {
            console.error('Login error:', error);
            alert('Login failed. Please try again.');
        }
    });
}

async function checkAuth() {
    const token = localStorage.getItem('authToken');
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (!token || !user) {
        window.location.href = 'login.html';
        return;
    }
    
    currentUser = user;
    setupDashboard();
    loadTasks();
    
    if (currentUser.role === 'admin') {
        document.getElementById('adminSection').style.display = 'block';
        loadUsers();
    }
    
    document.getElementById('logoutBtn').addEventListener('click', logout);
}

function setupDashboard() {
    // Setup create task button
    document.getElementById('createTaskBtn').addEventListener('click', showTaskModal);
    
    // Setup create user form if admin
    if (currentUser.role === 'admin') {
        document.getElementById('createUserForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = document.getElementById('newEmail').value;
            const password = document.getElementById('newPassword').value;
            const role = document.getElementById('role').value;
            
            try {
                const response = await fetch(`${API_BASE_URL}/users`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                    },
                    body: JSON.stringify({ name, email, password, role })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    alert('User created successfully');
                    document.getElementById('createUserForm').reset();
                    loadUsers();
                } else {
                    alert('Error creating user: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Create user error:', error);
                alert('Error creating user. Please try again.');
            }
        });
    }
}

async function loadTasks() {
    try {
        const response = await fetch(`${API_BASE_URL}/tasks`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        const tasks = await response.json();
        
        if (response.ok) {
            renderTasks(tasks);
        } else {
            console.error('Error loading tasks:', tasks);
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
    }
}


function renderTasks(tasks) {
    const taskList = document.getElementById('taskList');
    taskList.innerHTML = '';
    
    if (tasks.length === 0) {
        taskList.innerHTML = '<p>No tasks found.</p>';
        return;
    }
    
    const table = document.createElement('table');
    table.className = 'table table-striped table-hover';
    
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th>Title</th>
            <th>Assigned To</th>
            <th>Status</th>
            <th>Due Date</th>
            <th>Actions</th>
        </tr>
    `;
    table.appendChild(thead);
    
    const tbody = document.createElement('tbody');
    
    tasks.forEach(task => {
        const row = document.createElement('tr');
        
        // Tentukan aksi yang diperbolehkan
        const canEdit = (currentUser.role === 'admin') ||
                      (currentUser.role === 'manager' && task.assignee.role === 'staff') ||
                      (currentUser.role === 'staff' && task.assigned_to === currentUser.id);
        
        // Status badge
        let statusClass = 'badge bg-secondary';
        if (task.status === 'in_progress') statusClass = 'badge bg-warning text-dark';
        if (task.status === 'done') statusClass = 'badge bg-success';
        
        // Due date warning
        const dueDate = new Date(task.due_date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        let dueDateText = dueDate.toLocaleDateString();
        if (dueDate < today && task.status !== 'done') {
            dueDateText += ' (Overdue)';
            row.classList.add('table-danger');
        }
        
        row.innerHTML = `
            <td>${task.title}</td>
            <td>${task.assignee.name}</td>
            <td><span class="${statusClass}">${task.status.replace('_', ' ')}</span></td>
            <td>${dueDateText}</td>
            <td>
                ${canEdit ? `
                <button class="btn btn-sm btn-outline-primary edit-status" data-id="${task.id}" data-current-status="${task.status}">Edit Status</button>
                ` : '-'}
                ${currentUser.role === 'admin' || (currentUser.id === task.created_by) ? `
                <button class="btn btn-sm btn-outline-danger delete-task" data-id="${task.id}">Delete</button>
                ` : ''}
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    taskList.appendChild(table);
    
    // Add event listeners untuk edit status
    document.querySelectorAll('.edit-status').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const taskId = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-current-status');
            showStatusEditModal(taskId, currentStatus);
        });
    });
    
    // Add event listeners untuk delete
    document.querySelectorAll('.delete-task').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const taskId = this.getAttribute('data-id');
            deleteTask(taskId);
        });
    });
}

async function showStatusEditModal(taskId, currentStatus) {
    // Buat modal sederhana untuk edit status
    const modalHtml = `
        <div class="modal fade" id="statusEditModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Task Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="statusEditForm">
                            <input type="hidden" id="statusEditTaskId" value="${taskId}">
                            <div class="mb-3">
                                <label for="newStatus" class="form-label">Select New Status</label>
                                <select class="form-select" id="newStatus" required>
                                    <option value="pending" ${currentStatus === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="in_progress" ${currentStatus === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="done" ${currentStatus === 'done' ? 'selected' : ''}>Done</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveStatusBtn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Tambahkan modal ke DOM
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHtml;
    document.body.appendChild(modalContainer);
    
    // Tampilkan modal
    const modal = new bootstrap.Modal(document.getElementById('statusEditModal'));
    modal.show();
    
    // Setup save button
    document.getElementById('saveStatusBtn').addEventListener('click', async function() {
        const newStatus = document.getElementById('newStatus').value;
        await updateTaskStatus(taskId, newStatus);
        modal.hide();
        document.body.removeChild(modalContainer);
    });
    
    // Hapus modal saat ditutup
    document.getElementById('statusEditModal').addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modalContainer);
    });
}

async function updateTaskStatus(taskId, newStatus) {
    if (!taskId || !newStatus) return;
    
    try {
        const response = await fetch(`${API_BASE_URL}/tasks/${taskId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({ status: newStatus })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Failed to update status');
        }
        
        // Refresh tasks setelah update
        loadTasks();
        alert('Task status updated successfully');
    } catch (error) {
        console.error('Error updating task status:', error);
        alert('Failed to update task status: ' + error.message);
    }
}

async function showTaskModal(taskId = null) {
    // Pastikan taskId adalah string atau null
    if (taskId instanceof Event || typeof taskId !== 'string') {
        console.warn('Invalid task ID received:', taskId);
        taskId = null;
    }

    const modal = new bootstrap.Modal(document.getElementById('taskModal'));
    const modalTitle = document.getElementById('modalTitle');
    const taskForm = document.getElementById('taskForm');
    const assignedToSelect = document.getElementById('assignedTo');
    
    // Reset form
    taskForm.reset();
    assignedToSelect.innerHTML = '<option value="">Select User</option>';
    
    // Load user options
    try {
        const response = await fetch(`${API_BASE_URL}/users`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        const users = await response.json();
        
        if (response.ok) {
            // Filter users berdasarkan role
            const filteredUsers = users.filter(user => {
                if (currentUser.role === 'staff') return user.id === currentUser.id;
                if (currentUser.role === 'manager') return user.role === 'staff';
                return true; // Admin bisa melihat semua
            });
            
            // Tambahkan options
            filteredUsers.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                assignedToSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
    
    if (taskId) {
        // Edit mode
        modalTitle.textContent = 'Edit Task';
        
        try {
            const response = await fetch(`${API_BASE_URL}/tasks/${taskId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('authToken')}`
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch task');
            
            const task = await response.json();
            
            // Isi form dengan data task
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDescription').value = task.description;
            document.getElementById('assignedTo').value = task.assigned_to;
            document.getElementById('dueDate').value = task.due_date.split('T')[0];
            document.getElementById('taskStatus').value = task.status;
            
            // Nonaktifkan assignee untuk staff
            if (currentUser.role === 'staff') {
                document.getElementById('assignedTo').disabled = true;
            }
        } catch (error) {
            console.error('Error loading task:', error);
            alert('Failed to load task data');
            return;
        }
    } else {
        // Create mode
        modalTitle.textContent = 'Create Task';
        document.getElementById('taskStatus').value = 'pending';
        
        // Set default untuk staff
        if (currentUser.role === 'staff') {
            document.getElementById('assignedTo').value = currentUser.id;
            document.getElementById('assignedTo').disabled = true;
        }
    }
    
    modal.show();
}

async function saveTask() {
    const taskId = document.getElementById('taskId')?.value || null;
    
    // Ambil data dari form
    const taskData = {
        title: document.getElementById('taskTitle').value.trim(),
        description: document.getElementById('taskDescription').value.trim(),
        assigned_to: document.getElementById('assignedTo').value,
        due_date: document.getElementById('dueDate').value,
        status: document.getElementById('taskStatus').value
    };

    // Validasi client-side
    if (!taskData.title || !taskData.description || !taskData.assigned_to || !taskData.due_date) {
        alert('Please fill all required fields');
        return;
    }

    const url = taskId ? `${API_BASE_URL}/tasks/${taskId}` : `${API_BASE_URL}/tasks`;
    const method = taskId ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify(taskData)
        });

        const data = await response.json();

        if (!response.ok) {
            // Tampilkan error validasi jika ada
            if (data.errors) {
                const errorMessages = Object.entries(data.errors)
                    .map(([field, errors]) => `${field}: ${errors.join(', ')}`);
                alert('Validation errors:\n' + errorMessages.join('\n'));
            } else {
                alert(data.message || 'Failed to save task');
            }
            return;
        }

        // Sukses
        bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
        loadTasks();
        alert(taskId ? 'Task updated successfully' : 'Task created successfully');
    } catch (error) {
        console.error('Error saving task:', error);
        alert('Network error. Please try again.');
    }
}



async function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) return;
    
    try {
        const response = await fetch(`${API_BASE_URL}/tasks/${taskId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (response.ok) {
            loadTasks();
        } else {
            const data = await response.json();
            alert('Error deleting task: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error deleting task:', error);
        alert('Error deleting task. Please try again.');
    }
}

async function loadUsers() {
    try {
        const response = await fetch(`${API_BASE_URL}/users`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });

        const users = await response.json();

        if (response.ok) {
            const userList = document.getElementById('userList');
            userList.innerHTML = '';

            if (users.length === 0) {
                userList.innerHTML = '<p>No users found.</p>';
                return;
            }

            const listGroup = document.createElement('ul');
            listGroup.className = 'list-group';

            users.forEach(user => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
                    <span>
                        ${user.name} <small class="text-muted">(${user.role})</small><br>
                        <small>${user.email}</small>
                    </span>
                `;
                listGroup.appendChild(li);
            });

            userList.appendChild(listGroup);
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}


function renderUsers(users) {
    const userList = document.getElementById('userList');
    userList.innerHTML = '';
    
    if (users.length === 0) {
        userList.innerHTML = '<p>No users found.</p>';
        return;
    }
    
    const table = document.createElement('table');
    table.className = 'table table-striped table-hover';
    
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
        </tr>
    `;
    table.appendChild(thead);
    
    const tbody = document.createElement('tbody');
    
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Status badge
        const statusClass = user.status ? 'badge bg-success' : 'badge bg-danger';
        const statusText = user.status ? 'Active' : 'Inactive';
        
        // Role badge
        let roleClass = 'badge bg-secondary';
        if (user.role === 'admin') roleClass = 'badge bg-primary';
        if (user.role === 'manager') roleClass = 'badge bg-info text-dark';
        
        row.innerHTML = `
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td><span class="${roleClass}">${user.role}</span></td>
            <td><span class="${statusClass}">${statusText}</span></td>
        `;
        
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    userList.appendChild(table);
}

async function logout() {
    try {
        await fetch(`${API_BASE_URL}/logout`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        localStorage.removeItem('authToken');
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Logout error:', error);
    }
}
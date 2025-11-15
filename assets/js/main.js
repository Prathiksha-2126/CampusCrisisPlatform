//Main shared JS for Campus Crisis Platform
(function(){
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  //Modal controls
  function setupModal(){
    const modal = $('#aboutModal');
    const openBtn = $('#openAbout');
    if(!modal || !openBtn) return;
    const open = ()=>{ modal.classList.add('active'); modal.setAttribute('open',''); };
    const close = ()=>{ modal.classList.remove('active'); modal.removeAttribute('open'); };
    openBtn.addEventListener('click', open);
    $$('.modal-backdrop,[data-close-modal]', modal).forEach(el=> el.addEventListener('click', close));
  }

  //Authentication with PHP backend
  function setupAuth(){
    const form = $('#authForm');
    const toggleLogin = $('#toggleLogin');
    const toggleSignup = $('#toggleSignup');
    const title = $('#authTitle');
    const subtitle = $('#authSubtitle');
    const forgotRow = $('#forgotRow');
    const submitBtn = $('#authSubmit');
    let mode = 'login';
    
    function applyMode(){
      const isLogin = mode==='login';
      const nameLabel = $('#nameLabel');
      toggleLogin?.setAttribute('aria-pressed', isLogin?'true':'false');
      toggleSignup?.setAttribute('aria-pressed', !isLogin?'true':'false');
      title && (title.textContent = isLogin ? 'Welcome Back' : 'Create Account');
      subtitle && (subtitle.textContent = isLogin ? 'Log in to access your dashboard.' : 'Sign up to report issues and stay informed.');
      submitBtn && (submitBtn.textContent = isLogin ? 'Log In' : 'Sign Up');
      if(forgotRow){ forgotRow.style.display = isLogin ? '' : 'none'; }
      if(nameLabel){ nameLabel.style.display = isLogin ? 'none' : 'block'; }
    }

    toggleLogin?.addEventListener('click', ()=>{ mode='login'; applyMode(); });
    toggleSignup?.addEventListener('click', ()=>{ mode='signup'; applyMode(); });
    applyMode();

    const forgot = $('#forgotPassword');
    forgot?.addEventListener('click', (e)=>{ e.preventDefault(); alert('Password reset link sent (simulated).'); });

    if(form){
      form.addEventListener('submit', async (e)=>{
        e.preventDefault();
        const email = $('#authEmail').value.trim();
        const pwd = $('#authPassword').value.trim();
        const name = $('#authName')?.value?.trim() || '';
        
        //JavaScript validation
        if (!email || !pwd) {
          alert('Please fill in all required fields.');
          return;
        }
        
        if (mode === 'signup' && !name) {
          alert('Please enter your name.');
          return;
        }

        try {
          const endpoint = mode === 'login' ? 'auth/login.php' : 'auth/signup.php';
          const data = mode === 'login' 
            ? { email, password: pwd }
            : { name, email, password: pwd };

          const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
          });

          const result = await response.json();

          if (result.success) {
            // Redirect directly to dashboard without alert
            window.location.href = 'dashboard.html';
          } else {
            alert(result.message);
          }
        } catch (error) {
          console.error('Auth error:', error);
          alert('An error occurred. Please try again.');
        }
      });
    }
  }  
  console.log("renderDashboard() loaded!");

  async function fetchIssues() {
    try {
      const response = await fetch('api/get_alerts.php');
      console.log("Fetch triggered:", response);
      const data = await response.json();
      console.log("🔹 API Data:", data);
    } catch (err) {
      console.error("Fetch error:", err);
    }
  }

  // Dashboard alerts render (Live from PHP backend)
  async function renderDashboard() {
    const grid = document.getElementById('alertsGrid');
    if (!grid) return;

    // Add refresh indicator
    const refreshIndicator = document.getElementById('refreshIndicator');
    if (refreshIndicator) {
      refreshIndicator.style.display = 'inline-block';
    }

    // Clear existing
    grid.innerHTML = '<p class="muted center">Loading live campus issues...</p>';

    try {
      const response = await fetch('api/get_issues.php');
      const data = await response.json();

      console.log('Fetched issues:', data);

      if (!data.success) {
        grid.innerHTML = `<p class="error center">${data.message}</p>`;
        return;
      }

      const issues = data.issues || [];

      // Update KPIs
      const urgent = data.stats?.urgent || 0;
      const active = data.stats?.active || 0;
      const resolved = data.stats?.resolved_today || 0;
      document.getElementById('kpiUrgent').textContent = urgent;
      document.getElementById('kpiActive').textContent = active;
      document.getElementById('kpiResolved').textContent = resolved;

      // Handle empty case
      if (issues.length === 0) {
        grid.innerHTML = '<p class="muted center">No active campus issues reported.</p>';
        return;
      }
      // Sort newest first
      const sorted = issues.sort((a, b) => new Date(b.time) - new Date(a.time));

      // Render cards dynamically with click handlers
      grid.innerHTML = sorted.map(issue => `
      <article class="card alert-card ${issue.severity}" 
               data-issue-id="${issue.id}" 
               data-issue-data='${JSON.stringify(issue).replace(/'/g, "&apos;")}' 
               onclick="showIssueDetails(this)">
        <div class="meta"><span class="category">${issue.category.toUpperCase()}</span> · <span class="muted">${issue.time}</span></div>
        <div class="title">${issue.title}</div>
        <div class="muted">${issue.location}</div>
        <div class="badge ${issue.severity}"><i class="fa-solid fa-circle"></i> ${issue.status}</div>
        <p>${issue.description}</p>
      </article>
      `).join('');

      // Setup click event listeners for issue details
      setupIssueDetailsModal();

    } catch (err) {
      console.error('Error loading issues:', err);
      grid.innerHTML = '<p class="error center">❌ Failed to load issues. Please try again.</p>';
    } finally {
      // Hide refresh indicator
      const refreshIndicator = document.getElementById('refreshIndicator');
      if (refreshIndicator) {
        refreshIndicator.style.display = 'none';
      }
    }
  }
  // Report form with PHP backend
  function setupReport(){
    const form = $('#reportForm');
    if(!form) return;
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      
      // JavaScript validation with alerts
      const category = $('#issueCategory').value;
      const location = $('#issueLocation').value.trim();
      const description = $('#issueDescription').value.trim();
      const contact = $('#issueContact').value.trim();
      
      // Check if all required fields are filled
      if (!category || !location || !description || !contact) {
        alert('Please fill in all required fields before submitting.');
        return;
      }
      
      try {
        const data = {
          category,
          location,
          description,
          contact_info: contact,
          severity: 'yellow'
        };

        const response = await fetch('api/add_issue.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
          alert('Issue reported successfully!');
          form.reset();
          window.location.href = 'dashboard.html';
        } else {
          alert(result.message);
        }
      } catch (error) {
        console.error('Report error:', error);
        alert('An error occurred while submitting the report.');
      }
    });
  }

  // Admin table population with PHP backend
  async function renderAdminTable(){
    const tableBody = document.querySelector('#alertsTable tbody');
    if (!tableBody) {
      console.error('Admin table body not found');
      return;
    }
    
    try {
      console.log('Loading admin table...');
      // Fetch issues from database
      const response = await fetch('api/get_issues.php?limit=100');
      const result = await response.json();
      
      console.log('Admin API response:', result);
      
      if (!result.success) {
        console.error('Failed to load issues:', result.message);
        tableBody.innerHTML = '<tr><td colspan="6">Failed to load issues: ' + result.message + '</td></tr>';
        return;
      }
      
      const items = result.issues || [];
      console.log('Loaded', items.length, 'issues for admin table');
      
      if (items.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6">No issues found</td></tr>';
        return;
      }
      
      tableBody.innerHTML = items.map(a => {
        const issueId = a.id.replace('issue_', '');
        return `
      <tr data-id="${issueId}" data-type="issue">
        <td>${a.title}</td>
        <td>${a.category}</td>
        <td><span class="badge ${a.severity}">${a.severity.toUpperCase()}</span></td>

        <!-- Dropdown for status -->
        <td>
          <select class="status-select" data-issue-id="${issueId}">
            ${['Reported', 'Investigating', 'In Progress', 'Resolved', 'Delayed']
              .map(status => `<option value="${status}" ${status === a.status ? 'selected' : ''}>${status}</option>`)
              .join('')}
          </select>
        </td>

        <td>${a.location || ''}</td>

        <!-- Action buttons -->
        <td>
          <button class="icon-btn delete-btn" title="Delete" data-issue-id="${issueId}" aria-label="Delete">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2H8l1-2z"/>
            </svg>
          </button>
        </td>
      </tr>
        `;
      }).join('');
      
      // Setup event listeners after table is populated
      setupAdminEventListeners();
      
    } catch (error) {
      console.error('Error loading admin table:', error);
      tableBody.innerHTML = '<tr><td colspan="6">Error loading issues: ' + error.message + '</td></tr>';
    }
  }

  // Helper function to get severity color based on status
  function getSeverityFromStatus(status) {
    switch (status) {
      case 'Reported':
        return 'yellow';
      case 'Investigating':
        return 'red';
      case 'In Progress':
        return 'red';
      case 'Resolved':
        return 'green';
      case 'Delayed':
        return 'yellow';
      default:
        return 'yellow';
    }
  }

  // Separate function for admin event listeners
  function setupAdminEventListeners() {
    const tableBody = document.querySelector('#alertsTable tbody');
    
    // Remove existing listeners to prevent duplicates
    const newTableBody = tableBody.cloneNode(true);
    tableBody.parentNode.replaceChild(newTableBody, tableBody);
    
    // Add delete button listeners
    newTableBody.addEventListener('click', async (e) => {
      if (e.target.closest('.delete-btn')) {
        const btn = e.target.closest('.delete-btn');
        const issueId = btn.dataset.issueId;
        
        if (confirm('🗑️ Delete this issue permanently?')) {
          try {
            console.log('Deleting issue:', issueId);
            const response = await fetch('api/delete_issue.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ issue_id: parseInt(issueId) })
            });

            const result = await response.json();
            console.log('Delete response:', result);
            
            if (result.success) {
              alert(result.message);
              btn.closest('tr').remove();
              // Refresh dashboard
              if (typeof renderDashboard === 'function') renderDashboard();
            } else {
              alert(result.message);
            }
          } catch (error) {
            console.error('Delete error:', error);
            alert('An error occurred while deleting.');
          }
        }
      }
    });

    // Add status change listeners
    newTableBody.addEventListener('change', async (e) => {
      if (e.target.classList.contains('status-select')) {
        const select = e.target;
        const issueId = select.dataset.issueId;
        const newStatus = select.value;

        try {
          console.log('Updating status for issue:', issueId, 'to:', newStatus);
          const response = await fetch('api/update_issue_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
              issue_id: parseInt(issueId), 
              status: newStatus 
            })
          });

          const result = await response.json();
          console.log('Status update response:', result);
          
          if (result.success) {
            // Show success message briefly
            const originalBg = select.style.backgroundColor;
            select.style.backgroundColor = '#22c55e';
            setTimeout(() => {
              select.style.backgroundColor = originalBg;
            }, 1000);
            
            // Update severity badge based on new status
            const severityBadge = select.closest('tr').querySelector('.badge');
            const newSeverity = getSeverityFromStatus(newStatus);
            severityBadge.className = `badge ${newSeverity}`;
            severityBadge.textContent = newSeverity.toUpperCase();
            
            // Refresh dashboard to show updated status immediately
            if (typeof renderDashboard === 'function') {
              console.log('Triggering dashboard refresh after status update...');
              setTimeout(() => renderDashboard(), 500);
            }
            
            // Also trigger refresh on other open dashboard tabs
            broadcastDashboardRefresh();
          } else {
            alert('Failed to update status: ' + result.message);
            // Revert the select to previous value
            select.selectedIndex = 0;
          }
        } catch (error) {
          console.error('Status update error:', error);
          alert('An error occurred while updating status.');
          select.selectedIndex = 0;
        }
      }
    });
    
    // Setup filters
    const search = $('#filterSearch');
    const status = $('#filterStatus');
    function applyFilters(){
      const q = (search?.value || '').toLowerCase();
      const s = status?.value || '';
      $$('#alertsTable tbody tr').forEach(tr=>{
        const text = tr.textContent.toLowerCase();
        const statusCell = tr.children[3]?.textContent || '';
        const matchQ = !q || text.includes(q);
        const matchS = !s || statusCell === s;
        tr.style.display = (matchQ && matchS) ? '' : 'none';
      });
    }
    search?.addEventListener('input', applyFilters);
    status?.addEventListener('change', applyFilters);
  }

  // Campus Resources functions
  async function renderResourcesPanel() {
    const panel = document.getElementById('resourcesPanel');
    if (!panel) return;

    // Clear existing
    panel.innerHTML = '<p class="muted center">Loading campus resources...</p>';

    try {
      const response = await fetch('api/get_resources.php');
      const data = await response.json();

      console.log('Fetched resources:', data);

      if (!data.success) {
        panel.innerHTML = `<p class="error center">${data.message}</p>`;
        return;
      }

      const resources = data.resources || [];

      // Handle empty case
      if (resources.length === 0) {
        panel.innerHTML = '<p class="muted center">No resources available.</p>';
        return;
      }

      // Render resource cards
      panel.innerHTML = `
        <div class="grid cards">
          ${resources.map(resource => `
            <article class="card resource-card ${resource.is_available ? 'available' : 'unavailable'}">
              <div class="resource-header">
                <h3>${resource.name}</h3>
                <span class="resource-category">${resource.category}</span>
              </div>
              <div class="resource-status">
                <span class="status-badge status-${resource.status.toLowerCase().replace(/\s+/g, '-')}">${resource.status}</span>
                ${resource.quantity ? `<span class="quantity">${resource.quantity} ${resource.unit || ''}</span>` : ''}
              </div>
              ${resource.notes ? `<p class="resource-notes">${resource.notes}</p>` : ''}
              <div class="resource-meta">
                <small class="muted">Updated: ${resource.last_updated}</small>
                ${resource.updated_by ? `<small class="muted">by ${resource.updated_by}</small>` : ''}
              </div>
            </article>
          `).join('')}
        </div>
      `;

    } catch (error) {
      console.error('Error loading resources:', error);
      panel.innerHTML = '<p class="error center">Failed to load resources.</p>';
    }
  }

  // Admin resources functions
  async function loadAdminResources() {
    const tableBody = document.getElementById('resourcesAdminTableBody');
    const section = document.getElementById('resourcesAdminSection');
    if (!tableBody || !section) return;

    // Show the resources section
    section.style.display = 'block';

    try {
      const response = await fetch('api/get_resources.php');
      const data = await response.json();

      if (!data.success) {
        tableBody.innerHTML = `<tr><td colspan="9" class="center error">${data.message}</td></tr>`;
        return;
      }

      const resources = data.resources || [];

      if (resources.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="9" class="center muted">No resources found.</td></tr>';
        return;
      }

      tableBody.innerHTML = resources.map(resource => `
        <tr data-resource-id="${resource.resource_id}">
          <td>${resource.name}</td>
          <td>${resource.category}</td>
          <td>
            <select class="resource-status-select" data-resource-id="${resource.resource_id}">
              <option value="Available" ${resource.status === 'Available' ? 'selected' : ''}>Available</option>
              <option value="Low Stock" ${resource.status === 'Low Stock' ? 'selected' : ''}>Low Stock</option>
              <option value="Out of Stock" ${resource.status === 'Out of Stock' ? 'selected' : ''}>Out of Stock</option>
              <option value="Maintenance" ${resource.status === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
              <option value="Unavailable" ${resource.status === 'Unavailable' ? 'selected' : ''}>Unavailable</option>
            </select>
          </td>
          <td>
            <input type="number" class="resource-quantity-input" data-resource-id="${resource.resource_id}" 
                   value="${resource.quantity || ''}" min="0" placeholder="Qty">
          </td>
          <td>
            <input type="text" class="resource-unit-input" data-resource-id="${resource.resource_id}" 
                   value="${resource.unit || ''}" placeholder="Unit">
          </td>
          <td>
            <input type="checkbox" class="resource-available-checkbox" data-resource-id="${resource.resource_id}" 
                   ${resource.is_available ? 'checked' : ''}>
          </td>
          <td>
            <textarea class="resource-notes-input" data-resource-id="${resource.resource_id}" 
                      placeholder="Notes">${resource.notes || ''}</textarea>
          </td>
          <td><small class="muted">${resource.last_updated}</small></td>
          <td>
            <button class="btn small" onclick="saveResource(${resource.resource_id})">Save</button>
          </td>
        </tr>
      `).join('');

    } catch (error) {
      console.error('Error loading admin resources:', error);
      tableBody.innerHTML = '<tr><td colspan="9" class="center error">Failed to load resources.</td></tr>';
    }
  }

  // Save resource function (global for onclick)
  window.saveResource = async function(resourceId) {
    const row = document.querySelector(`tr[data-resource-id="${resourceId}"]`);
    if (!row) return;

    const status = row.querySelector('.resource-status-select').value;
    const quantity = row.querySelector('.resource-quantity-input').value;
    const unit = row.querySelector('.resource-unit-input').value;
    const isAvailable = row.querySelector('.resource-available-checkbox').checked;
    const notes = row.querySelector('.resource-notes-input').value;

    const updateData = {
      resource_id: resourceId,
      status: status,
      quantity: quantity ? parseInt(quantity) : null,
      unit: unit || null,
      is_available: isAvailable,
      notes: notes || null,
      updated_by: 'admin'
    };

    try {
      const response = await fetch('api/update_resource.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin', // Include session cookies
        body: JSON.stringify(updateData)
      });

      const result = await response.json();

      if (result.success) {
        alert('Resource updated successfully!');
        // Refresh both admin table and dashboard resources
        loadAdminResources();
        renderResourcesPanel();
      } else {
        alert('Failed to update resource: ' + result.message);
      }
    } catch (error) {
      console.error('Error updating resource:', error);
      alert('An error occurred while updating the resource.');
    }
  };

  // Admin password prompt simulation
  function setupAdminGuard(){
    if(!$('#alertsTable')) return;
    const ok = sessionStorage.getItem('ccp_admin_ok');
    if(ok==='1') {
      // Also ensure server-side session is set
      fetch('api/admin_login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ password: 'admin123' })
      }).then(response => response.json())
        .then(result => {
          if (result.success) {
            console.log('Server-side admin session verified');
          }
        })
        .catch(error => {
          console.error('Error verifying server session:', error);
        });
      
      renderAdminTable();
      loadAdminResources();
      return;
    }

    const pwd = prompt('Admin password:');

    if(pwd === null){
      alert('Admin access cancelled.');
      window.location.href = 'dashboard.html';
      return;
    }

    if(pwd.trim() === 'admin123'){
      // Set client-side session
      sessionStorage.setItem('ccp_admin_ok','1');
      
      // Also set server-side session
      fetch('api/admin_login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ password: pwd.trim() })
      }).then(response => response.json())
        .then(result => {
          if (result.success) {
            console.log('Server-side admin session set');
          }
        })
        .catch(error => {
          console.error('Error setting server session:', error);
        });
      
      renderAdminTable();
      loadAdminResources();
    } else {
      alert('Incorrect password.');
      window.location.href = 'dashboard.html';
    }
  }

  // Forum page with PHP backend
  function setupForum() {
    const listEl = $('#postsList');
    if (!listEl) {
      console.log('Posts list element not found');
      return;
    }
    console.log('Setting up forum, found posts list element');
    let posts = [];

    async function loadPosts() {
      console.log('Loading posts...');
      try {
        const response = await fetch('api/get_posts.php');
        console.log('Response status:', response.status, response.statusText);
        
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        console.log('Posts API response:', result);

        if (result.success) {
          posts = result.posts;
          console.log('Posts loaded:', posts.length, 'posts');
          render();
        } 
        else {
          console.error('API returned error:', result.message);
          listEl.innerHTML = `<p class="muted">Failed to load posts: ${result.message}</p>`;
        }
      } catch (error) {
        console.error('Error loading posts:', error);
        listEl.innerHTML = `<p class="muted">Could not fetch posts from server.</p>`;
      }
    }
    function render() {
      console.log('Rendering posts:', posts);
      if (!posts.length) {
        listEl.innerHTML = `<p class="muted center">No posts yet. Be the first to share an update!</p>`;
        return;
      }

      listEl.innerHTML = posts
      .map(
        (p) => `
        <article class="post row">
        <div class="avatar" aria-hidden="true">${(p.author || 'U')
          .split(/\s+/)
          .map((s) => s[0])
          .join('')
          .slice(0, 2)
          .toUpperCase()}</div>
        <div class="content">
          <div class="author">${p.author} <span class="time">· ${p.time || 'Just now'}</span></div>
          <p>${p.text}</p>
        </div>
      </article>`
    )
    .join('');
}


    // Load posts on page load
    loadPosts();

    // Handle form submission
    const form = $('#postForm');
    form?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const author = $('#postAuthor').value.trim();
      const text = $('#postText').value.trim();
      
      if (!author || !text) {
        alert('Please fill out this field.');
        return;
      }
      
      try {
        const response = await fetch('api/add_post.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_name: author, message: text })
        });

        const result = await response.json();

        if (result.success) {
          alert('Post submitted successfully!');
          $('#postAuthor').value = '';
          $('#postText').value = '';
          console.log('Post submitted successfully, reloading posts...');
          loadPosts(); // Reload posts from server
        } else {
          alert(result.message);
        }
      } catch (error) {
        console.error('Post error:', error);
        alert('An error occurred while posting.');
      }
    });
  }

  // Highlight active nav link by URL
  function highlightActiveNav(){
    const links = $$('.nav a');
    links.forEach(a=>{
      if(location.pathname.endsWith(a.getAttribute('href'))){
        a.classList.add('active');
      }
    });
  }

  // Setup dashboard auto-refresh functionality
  function setupDashboardAutoRefresh() {
    const grid = document.getElementById('alertsGrid');
    if (!grid) return; // Only run on dashboard page
    
    console.log('Setting up dashboard auto-refresh...');
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
      console.log('Auto-refreshing dashboard...');
      renderDashboard();
    }, 30000);
    
    // Add refresh button to dashboard
    addRefreshButton();
  }

  // Add manual refresh button to dashboard
  function addRefreshButton() {
    const heroActions = document.querySelector('.hero-actions');
    if (!heroActions) return;
    
    const refreshBtn = document.createElement('button');
    refreshBtn.className = 'btn secondary';
    refreshBtn.innerHTML = '<i class="fa-solid fa-refresh"></i> Refresh';
    refreshBtn.onclick = () => {
      console.log('Manual refresh triggered');
      renderDashboard();
    };
    
    heroActions.appendChild(refreshBtn);
  }

  // Enhanced dashboard render with loading states
  async function renderDashboardWithLoading() {
    const grid = document.getElementById('alertsGrid');
    if (!grid) return;

    // Show loading state
    const originalContent = grid.innerHTML;
    grid.innerHTML = '<p class="muted center">🔄 Refreshing data...</p>';
    
    try {
      await renderDashboard();
    } catch (error) {
      console.error('Dashboard refresh failed:', error);
      grid.innerHTML = originalContent; // Restore previous content on error
    }
  }

  // Cross-tab communication for dashboard refresh
  function broadcastDashboardRefresh() {
    // Use localStorage to communicate between tabs
    localStorage.setItem('dashboardRefresh', Date.now().toString());
  }

  // Issue Details Modal Functions
  function showIssueDetails(cardElement) {
    try {
      const issueData = JSON.parse(cardElement.dataset.issueData.replace(/&apos;/g, "'"));
      
      // Create modal content
      const modalContent = `
        <div class="issue-details-modal">
          <div class="modal-header">
            <h2>${issueData.title}</h2>
            <span class="badge ${issueData.severity}">${issueData.status}</span>
          </div>
          <div class="modal-body">
            <div class="detail-grid">
              <div class="detail-item">
                <strong>📍 Location:</strong>
                <span>${issueData.location}</span>
              </div>
              <div class="detail-item">
                <strong>📂 Category:</strong>
                <span>${issueData.category.toUpperCase()}</span>
              </div>
              <div class="detail-item">
                <strong>🔥 Severity:</strong>
                <span class="badge ${issueData.severity}">${issueData.severity.toUpperCase()}</span>
              </div>
              <div class="detail-item">
                <strong>⏱️ Status:</strong>
                <span class="badge ${issueData.severity}">${issueData.status}</span>
              </div>
              <div class="detail-item">
                <strong>🕒 Reported:</strong>
                <span>${issueData.time}</span>
              </div>
              <div class="detail-item full-width">
                <strong>📝 Description:</strong>
                <p>${issueData.description}</p>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Show modal
      showModal('Issue Details', modalContent);
      
    } catch (error) {
      console.error('Error showing issue details:', error);
      alert('Error loading issue details');
    }
  }

  function setupIssueDetailsModal() {
    // Modal is already handled by the existing modal system
    console.log('Issue details modal ready');
  }

  // Enhanced modal function
  function showModal(title, content) {
    const modal = document.getElementById('aboutModal') || createModal();
    const modalDialog = modal.querySelector('.modal-dialog');
    
    modalDialog.innerHTML = `
      <button class="modal-close" onclick="closeModal()">&times;</button>
      <h3>${title}</h3>
      <div class="modal-content-wrapper">
        ${content}
      </div>
    `;
    
    modal.classList.add('active');
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    const modal = document.getElementById('aboutModal');
    if (modal) {
      modal.classList.remove('active');
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  function createModal() {
    const modal = document.createElement('div');
    modal.id = 'aboutModal';
    modal.className = 'modal';
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML = `
      <div class="modal-backdrop" onclick="closeModal()"></div>
      <div class="modal-dialog"></div>
    `;
    document.body.appendChild(modal);
    return modal;
  }

  // Make functions global
  window.showIssueDetails = showIssueDetails;
  window.closeModal = closeModal;

  // 💬 PENDING FORUM POSTS MODERATION FUNCTIONS
  async function loadPendingPosts() {
    const tableBody = document.querySelector('#pendingPostsTable tbody');
    const pendingCount = document.getElementById('pendingPostsCount');
    
    if (!tableBody) return;

    try {
      const response = await fetch('api/list_pending_posts.php');
      const result = await response.json();

      if (!result.success) {
        tableBody.innerHTML = `<tr><td colspan="4" class="center error">${result.message}</td></tr>`;
        return;
      }

      const pendingPosts = result.posts || [];
      
      // Update count badge
      if (pendingCount) {
        pendingCount.textContent = pendingPosts.length;
        pendingCount.className = pendingPosts.length > 0 ? 'badge red' : 'badge green';
      }

      if (pendingPosts.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4" class="center muted">No pending forum posts</td></tr>';
        return;
      }

      // Render pending posts
      tableBody.innerHTML = pendingPosts.map(post => `
        <tr data-post-id="${post.post_id}">
          <td><strong>${post.user_name}</strong></td>
          <td style="max-width: 300px; word-wrap: break-word;">${post.message}</td>
          <td class="muted small">${post.created_at}</td>
          <td>
            <button class="btn small secondary" onclick="approvePost(${post.post_id}, true)">
              Approve
            </button>
            <button class="btn small" onclick="approvePost(${post.post_id}, false)" style="margin-left: 5px;">
              Reject
            </button>
          </td>
        </tr>
      `).join('');

    } catch (error) {
      console.error('Error loading pending posts:', error);
      tableBody.innerHTML = '<tr><td colspan="4" class="center error">Error loading pending posts</td></tr>';
    }
  }

  async function approvePost(postId, approve) {
    const action = approve ? 'approve' : 'reject';
    
    if (!confirm(`Are you sure you want to ${action} this forum post?`)) {
      return;
    }

    try {
      const response = await fetch('api/approve_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, approve: approve })
      });

      const result = await response.json();

      if (result.success) {
        alert(`Forum post ${approve ? 'approved' : 'rejected'} successfully!`);
        
        // Refresh pending posts list
        loadPendingPosts();
        
      } else {
        alert(result.message);
      }

    } catch (error) {
      console.error('Error processing post:', error);
      alert('An error occurred while processing the request.');
    }
  }

  // Make moderation functions global
  window.loadPendingPosts = loadPendingPosts;
  window.approvePost = approvePost;

  // Listen for dashboard refresh broadcasts
  function setupCrossTabRefresh() {
    window.addEventListener('storage', (e) => {
      if (e.key === 'dashboardRefresh' && document.getElementById('alertsGrid')) {
        console.log('Received dashboard refresh broadcast from another tab');
        renderDashboard();
        renderResourcesPanel();
      }
    });
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    setupModal();
    setupAuth();
    renderDashboard();
    renderResourcesPanel();
    setupReport();
    setupAdminGuard();
    setupForum();
    highlightActiveNav();
    
    // Setup auto-refresh for dashboard
    setupDashboardAutoRefresh();
    
    // Setup cross-tab refresh communication
    setupCrossTabRefresh();
    
    // Load pending forum posts on admin page
    if (document.getElementById('pendingPostsTable')) {
      loadPendingPosts();
      // Auto-refresh pending posts every 30 seconds
      setInterval(loadPendingPosts, 30000);
    }
  });

 })();

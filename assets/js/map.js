// Enhanced PCCE Campus Map Logic
(function() {
  function init() {
    const mapEl = document.getElementById('map');
    if (!mapEl || !window.L) return;

    // Center map on PCCE Verna
    const map = L.map('map').setView([15.3925, 73.8782], 17);

    // Natural green map theme - OpenStreetMap
    console.log('🌿 Loading natural OpenStreetMap tiles...');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Static PCCE Facilities - Clean circular markers
    const facilities = [
      { name: 'Main Building', coords: [15.3927, 73.8783], type: 'building' },
      { name: 'Hostel Area', coords: [15.3932, 73.8779], type: 'residence' },
      { name: 'Canteen', coords: [15.3929, 73.8787], type: 'dining' },
      { name: 'Parking', coords: [15.3923, 73.8784], type: 'parking' }
    ];

    facilities.forEach(facility => {
      L.circleMarker(facility.coords, {
        radius: 8,
        fillColor: '#3b82f6',
        color: '#1e40af',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8,
        className: 'facility-marker'
      }).addTo(map).bindPopup(`<b>${facility.name}</b><br>Campus Facility`);
    });

    // 📍 Live Issues (Dynamic from Database)
    let markers = [];

    async function loadAlerts() {
      try {
        const res = await fetch('api/get_issues.php');
        const data = await res.json();
        console.log('Issues data:', data);

        // Properly clear old markers to prevent flickering
        markers.forEach(m => {
          if (map.hasLayer(m)) {
            map.removeLayer(m);
          }
        });
        markers.length = 0; // Clear array

        if (!data.success || !data.issues) return;

        data.issues.forEach(issue => {
          // Generate realistic coordinates around PCCE campus
          const lat = 15.3925 + (Math.random() - 0.5) * 0.003; // ~300m spread
          const lng = 73.8782 + (Math.random() - 0.5) * 0.004; // ~400m spread

          // Determine colors based on severity and status
          const severityColor = getSeverityColor(issue.severity);
          const statusColor = getStatusColor(issue.status);
          
          // Create clean marker without flickering animations
          const marker = L.circleMarker([lat, lng], {
            color: statusColor,
            fillColor: severityColor,
            fillOpacity: 0.7,
            radius: issue.severity === 'red' ? 10 : 8,
            weight: 2,
            opacity: 0.9
          }).addTo(map);

          // Enhanced popup with professional styling
          marker.bindPopup(`
            <div class="issue-popup">
              <h4>${issue.title}</h4>
              <p><strong>Location:</strong> ${issue.location}</p>
              <p><strong>Category:</strong> ${issue.category}</p>
              <p><strong>Status:</strong> <span class="badge ${issue.severity}">${issue.status}</span></p>
              <p><strong>Severity:</strong> <span class="badge ${issue.severity}">${issue.severity.toUpperCase()}</span></p>
              <p><strong>Time:</strong> ${issue.time}</p>
              <p class="description">${issue.description}</p>
            </div>
          `);

          marker.alertData = issue;
          markers.push(marker);
        });

        // 🕓 Update last refreshed time
        document.getElementById('mapUpdateTime').textContent =
          new Date().toLocaleTimeString();
      } catch (err) {
        console.error('Error loading alerts:', err);
      }
    }

    // 🎨 Color Helper Functions
    function getSeverityColor(severity) {
      const colors = {
        'red': '#ef4444',     // Bright red for urgent
        'yellow': '#f59e0b',  // Orange for medium
        'green': '#16a34a'    // Green for resolved
      };
      return colors[severity] || '#60a5fa';
    }

    function getStatusColor(status) {
      const statusColors = {
        'Reported': '#f59e0b',      // Orange border
        'Investigating': '#ef4444',  // Red border
        'In Progress': '#dc2626',    // Dark red border
        'Resolved': '#16a34a',       // Green border
        'Delayed': '#f59e0b'         // Orange border
      };
      return statusColors[status] || '#6b7280';
    }

    // 🌍 Filter Functionality
    function setupFilters() {
      const buttons = document.querySelectorAll('.map-filters .btn');
      
      // Set default active filter
      buttons[0]?.classList.add('active');
      
      buttons.forEach(btn => {
        btn.addEventListener('click', () => {
          const filter = btn.dataset.filter;
          buttons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          
          // Use the enhanced filter function
          applyFilter(filter);
        });
      });
    }

    // 🔄 Auto-refresh functionality
    function setupAutoRefresh() {
      // Initial load
      loadAlerts();
      
      // Auto-refresh every 30 seconds
      setInterval(() => {
        console.log('🔄 Auto-refreshing map data...');
        loadAlerts();
      }, 30000);
      
      // Listen for admin changes (cross-tab communication)
      window.addEventListener('storage', (e) => {
        if (e.key === 'dashboardRefresh') {
          console.log('🔄 Received refresh signal from admin panel');
          setTimeout(loadAlerts, 1000); // Small delay to ensure DB is updated
        }
      });
    }

    // 🎯 Enhanced filter with better logic
    function applyFilter(filter) {
      console.log('🌍 Applying filter:', filter);
      
      markers.forEach(m => {
        const issue = m.alertData;
        let visible = false;
        
        switch(filter) {
          case 'all':
            visible = true;
            break;
          case 'urgent':
            visible = issue.severity === 'red' || issue.status === 'Investigating';
            break;
          case 'active':
            visible = ['Reported', 'In Progress', 'Investigating'].includes(issue.status);
            break;
          case 'resolved':
            visible = issue.status === 'Resolved';
            break;
        }
        
        if (visible) {
          m.addTo(map);
        } else {
          map.removeLayer(m);
        }
      });
    }

    // Initialize everything
    setupAutoRefresh();
    setupFilters();

    // Fix map display resize bug
    setTimeout(() => {
      map.invalidateSize();
    }, 100);
  }

  document.addEventListener('DOMContentLoaded', init);
})();

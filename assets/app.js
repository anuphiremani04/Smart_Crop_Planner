
document.addEventListener('DOMContentLoaded', function(){
  // Tabs on login page
  function setupTabs(containerId){
    const container = document.getElementById(containerId);
    if(!container) return;
    const tabs = container.querySelectorAll('.tab');
    const panes = container.querySelectorAll('.tab-pane');
    tabs.forEach((t, idx) => {
      t.addEventListener('click', () => {
        tabs.forEach(x => x.classList.remove('active'));
        panes.forEach(p => p.style.display = 'none');
        t.classList.add('active');
        panes[idx].style.display = 'block';
      });
    });
  }
  setupTabs('login-tabs');

  // When clicking register link in login, forward with role param
  const registerLinks = document.querySelectorAll('.open-register');
  registerLinks.forEach(l => {
    l.addEventListener('click', function(e){
      e.preventDefault();
      const role = this.dataset.role || 'user';
      // navigate to register.html with hash for role
      location.href = 'register.html#' + role;
    });
  });

  // Tab switching for register page
  function setupRegisterTabs(){
    const container = document.getElementById('register-tabs');
    if(!container) return;
    const tabs = container.querySelectorAll('.tab');
    const panes = container.querySelectorAll('.tab-pane');
    
    // Show form based on hash on page load
    function showRegisterRole(){
      const hash = location.hash.replace('#','') || 'user';
      tabs.forEach((t, idx) => {
        const role = t.dataset.role || 'user';
        if(role === hash){
          tabs.forEach(x => x.classList.remove('active'));
          panes.forEach(p => p.style.display = 'none');
          t.classList.add('active');
          // Find the correct pane by matching role
          const targetPane = Array.from(panes).find((p, i) => {
            // First pane is farmer (user), second is admin
            return (hash === 'user' && i === 0) || (hash === 'admin' && i === 1);
          });
          if(targetPane) targetPane.style.display = 'block';
        }
      });
    }
    
    // Update hash when tab is clicked
    tabs.forEach((t, idx) => {
      t.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        const role = t.dataset.role || 'user';
        location.hash = role;
        showRegisterRole();
      });
    });
    
    showRegisterRole();
    window.addEventListener('hashchange', showRegisterRole);
  }
  setupRegisterTabs();

  // Login handlers
  function setStatus(statusId, message){
    const el = document.getElementById(statusId);
    if(el) el.textContent = message;
  }

  // User login handler
  const userLoginForm = document.getElementById('user-login-form');
  if(userLoginForm){
    userLoginForm.addEventListener('submit', async function(e){
      e.preventDefault();
      const username = document.getElementById('user-login-username')?.value.trim();
      const password = document.getElementById('user-login-password')?.value || '';
      if(!username || !password){
        setStatus('user-login-status', 'Enter both username and password.');
        return;
      }
      setStatus('user-login-status', 'Authenticating...');
      try{
        const res = await fetch('api/login.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({username, password})
        });
        const data = await res.json();
        if(!res.ok || !data.success){
          setStatus('user-login-status', data.error || 'Invalid credentials.');
          return;
        }
        localStorage.setItem('user', JSON.stringify(data.user || {}));
        setStatus('user-login-status', 'Login successful. Redirecting...');
        setTimeout(() => { window.location.href = 'user_dashboard.html'; }, 600);
      }catch(err){
        console.error(err);
        setStatus('user-login-status', 'Unable to reach server.');
      }
    });
  }

  // Admin login handler
  const adminLoginForm = document.getElementById('admin-login-form');
  if(adminLoginForm){
    adminLoginForm.addEventListener('submit', async function(e){
      e.preventDefault();
      const username = document.getElementById('admin-login-username')?.value.trim();
      const password = document.getElementById('admin-login-password')?.value || '';
      if(!username || !password){
        setStatus('admin-login-status', 'Enter both username and password.');
        return;
      }
      setStatus('admin-login-status', 'Authenticating...');
      try{
        const res = await fetch('api/admin_login.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({username, password})
        });
        const data = await res.json();
        if(!res.ok || !data.success){
          setStatus('admin-login-status', data.error || 'Invalid credentials.');
          return;
        }
        localStorage.setItem('admin_token', data.token || '');
        localStorage.setItem('admin_user', JSON.stringify(data.admin || {}));
        setStatus('admin-login-status', 'Login successful. Redirecting...');
        setTimeout(() => { window.location.href = 'admin/admin_dashboard.html'; }, 600);
      }catch(err){
        console.error(err);
        setStatus('admin-login-status', 'Unable to reach server.');
      }
    });
  }

  // Registration handlers
  function attachRegisterHandler({formId, statusId, endpoint, payload, successRedirect, requiredFields = []}){
    const form = document.getElementById(formId);
    if(!form) return;
    form.addEventListener('submit', async function(e){
      e.preventDefault();
      const body = payload();
      if(!body){
        setStatus(statusId,'Form payload invalid.');
        return;
      }
      const missing = requiredFields.some(key => {
        const val = body[key];
        return typeof val !== 'string' ? !val : val.trim() === '';
      });
      if(missing){
        setStatus(statusId,'Please fill in all required fields.');
        return;
      }
      setStatus(statusId,'Submitting...');
      try{
        const res = await fetch(endpoint, {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(body)
        });
        const data = await res.json();
        if(!res.ok || !data.success){
          setStatus(statusId,(data && data.error) ? data.error : 'Registration failed.');
          return;
        }
        setStatus(statusId,'Registration successful. Redirecting...');
        setTimeout(()=>{ window.location.href = successRedirect; }, 800);
      }catch(err){
        console.error(err);
        setStatus(statusId,'Unable to reach server.');
      }
    });
  }

  attachRegisterHandler({
    formId: 'farmer-register-form',
    statusId: 'farmer-register-status',
    endpoint: 'api/register.php',
    successRedirect: 'login.html',
    requiredFields: ['name','email','username','password'],
    payload: () => ({
      name: document.getElementById('reg-name')?.value.trim(),
      email: document.getElementById('reg-email')?.value.trim(),
      username: document.getElementById('reg-username')?.value.trim(),
      password: document.getElementById('reg-password')?.value || '',
      contact: document.getElementById('reg-contact')?.value.trim(),
      location: document.getElementById('reg-location')?.value.trim(),
      soil: document.getElementById('reg-soil')?.value
    })
  });

  attachRegisterHandler({
    formId: 'admin-register-form',
    statusId: 'admin-register-status',
    endpoint: 'api/admin_register.php',
    successRedirect: 'login.html#admin',
    requiredFields: ['username','password'],
    payload: () => ({
      username: document.getElementById('admin-reg-username')?.value.trim(),
      password: document.getElementById('admin-reg-password')?.value || ''
    })
  });
});

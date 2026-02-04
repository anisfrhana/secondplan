
    const assigneeInput = document.getElementById('assignee');
    const suggestions = document.getElementById('assignee-suggestions');
    
    // Show/hide suggestions
    assigneeInput.addEventListener('focus', () => {
      suggestions.classList.add('show');
    });
    
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.field')) {
        suggestions.classList.remove('show');
      }
    });
    
    // Select assignee
    function selectAssignee(name) {
      assigneeInput.value = name;
      suggestions.classList.remove('show');
      updateSummary();
    }
    
    // Priority selection
    function selectPriority(level) {
      document.getElementById('priority').value = level;
      document.querySelectorAll('.priority-option').forEach(opt => {
        opt.classList.remove('selected');
      });
      document.querySelector(`.priority-option.${level}`).classList.add('selected');
      updateSummary();
    }
    
    // Update summary
    function updateSummary() {
      const title = document.getElementById('title').value;
      const assignee = assigneeInput.value;
      const dueDate = document.getElementById('due_date').value;
      const priority = document.getElementById('priority').value;
      
      const summary = document.getElementById('task-summary');
      
      if (title || assignee || dueDate) {
        summary.classList.add('show');
        
        document.getElementById('summary-title').textContent = title || '—';
        document.getElementById('summary-assignee').textContent = assignee || '—';
        
        if (dueDate) {
          const d = new Date(dueDate);
          document.getElementById('summary-date').textContent = d.toLocaleDateString('en-MY', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
          });
        } else {
          document.getElementById('summary-date').textContent = '—';
        }
        
        document.getElementById('summary-priority').textContent = 
          priority.charAt(0).toUpperCase() + priority.slice(1);
      } else {
        summary.classList.remove('show');
      }
    }
    
    // Attach update listeners
    ['title', 'assignee', 'due_date'].forEach(id => {
      document.getElementById(id).addEventListener('input', updateSummary);
    });
    
    // Set minimum date to today
    const dateInput = document.getElementById('due_date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;
    
    // Validation
    function validateField(field) {
      const fieldDiv = field.closest('.field');
      if (!fieldDiv) return true;
      
      fieldDiv.classList.remove('error');
      
      if (field.required && !field.value.trim()) {
        fieldDiv.classList.add('error');
        return false;
      }
      
      if (field.type === 'date' && field.value) {
        const selected = new Date(field.value);
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        if (selected < now) {
          fieldDiv.classList.add('error');
          fieldDiv.querySelector('.validation-error').textContent = 'Due date cannot be in the past';
          return false;
        }
      }
      
      return true;
    }
    
    document.querySelectorAll('#form input, #form textarea').forEach(field => {
      field.addEventListener('blur', () => validateField(field));
    });

/**
 * Reusable multi-skill chip input.
 * Builds a comma-separated list into a hidden <input> so existing server-side
 * code (which reads $_POST['skill'] as a single string) needs no changes.
 */
function initSkillsInput(inputId, hiddenId, chipsId, initialValue, onChange) {
    const input  = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const chips  = document.getElementById(chipsId);
    if (!input || !hidden || !chips) return;

    let skills = (initialValue || '')
        .split(',')
        .map(s => s.trim())
        .filter(Boolean);

    function render() {
        chips.innerHTML = '';
        skills.forEach((s, i) => {
            const chip = document.createElement('span');
            chip.className = 'skill-chip';

            const label = document.createElement('span');
            label.textContent = s;

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'skill-chip-remove';
            rm.setAttribute('aria-label', 'Remove ' + s);
            rm.textContent = '×';
            rm.onclick = () => { skills.splice(i, 1); render(); };

            chip.appendChild(label);
            chip.appendChild(rm);
            chips.appendChild(chip);
        });
        hidden.value = skills.join(', ');
        if (onChange) onChange();
    }

    function addFromInput() {
        const v = input.value.trim();
        if (v && !skills.some(s => s.toLowerCase() === v.toLowerCase())) {
            skills.push(v);
            render();
        }
        input.value = '';
    }

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addFromInput();
        } else if (e.key === 'Backspace' && input.value === '' && skills.length) {
            skills.pop();
            render();
        }
    });
    input.addEventListener('blur', addFromInput);

    render();
}

/* eslint-disable prettier/prettier */
/* eslint-disable @wordpress/no-unused-vars-before-return */
/* eslint-disable no-alert */
// @ts-ignore
import './styles/admin.scss';

/**
 * Reply to quote form: live line totals, add and remove items.
 * Runs only when the form is on the page.
 */
const initReplyForm = () => {
	const form = document.getElementById('doitgq-reply-form');
	const rows = document.getElementById('doitgq-rows');

	if (!form || !rows) {
		return;
	}

	// @ts-ignore
	const el = (id) => document.getElementById(id);
	// @ts-ignore
	const num = (value) => parseFloat(value) || 0;
	// @ts-ignore
	const fmt = (n) => n.toFixed(2);
	let next = rows.children.length;

	const recalc = () => {
		let subtotal = 0;

		rows.querySelectorAll('tr').forEach((tr) => {
			// @ts-ignore
			const qty = num(tr.querySelector('.doitgq-qty').value);
			// @ts-ignore
			const price = num(tr.querySelector('.doitgq-price').value);
			// @ts-ignore
			const disc = num(tr.querySelector('.doitgq-disc').value);
			const line = qty * price * (1 - disc / 100);

			// @ts-ignore
			tr.querySelector('.doitgq-line').textContent = fmt(line);
			subtotal += line;
		});

		// @ts-ignore
		const discount = (subtotal * num(el('doitgq-discount').value)) / 100;
		const taxable = subtotal - discount;
		// @ts-ignore
		const tax = (taxable * num(el('doitgq-tax').value)) / 100;

		// @ts-ignore
		el('doitgq-subtotal').textContent = fmt(subtotal);
		// @ts-ignore
		el('doitgq-discamt').textContent = fmt(discount);
		// @ts-ignore
		el('doitgq-taxamt').textContent = fmt(tax);
		// @ts-ignore
		el('doitgq-total').textContent = fmt(taxable + tax);
	};

	form.addEventListener('input', recalc);

	// @ts-ignore
	el('doitgq-add').addEventListener('click', () => {
		const i = next++;
		const tr = document.createElement('tr');

		tr.innerHTML =
			`<td><input type="text" name="items[${i}][name]"></td>` +
			`<td><input type="text" name="items[${i}][desc]"></td>` +
			`<td><input type="number" name="items[${i}][qty]" value="1" min="0" step="any" class="doitgq-qty"></td>` +
			`<td><input type="number" name="items[${i}][price]" value="0" min="0" step="any" class="doitgq-price"></td>` +
			`<td><input type="number" name="items[${i}][discount]" value="0" min="0" max="100" step="any" class="doitgq-disc"></td>` +
			'<td class="doitgq-line">0.00</td>' +
			'<td><button type="button" class="button doitgq-remove">&times;</button></td>';

		rows.appendChild(tr);
		recalc();
	});

	rows.addEventListener('click', (event) => {
		// @ts-ignore
		if (event.target.classList.contains('doitgq-remove')) {
			// @ts-ignore
			event.target.closest('tr').remove();
			recalc();
		}
	});

	recalc();
};

/**
 * WooCommerce > Get a Quote settings: show the preview of the selected form
 * and let the SMTP password be shown or hidden.
 */
const initSettingsTab = () => {
	const select = document.getElementById('doitgq_form_type');
	const password = document.getElementById('doitgq_mailer_password');

	if (select) {
		const rows = document.querySelectorAll('.doitgq-preview-row');
		const showSelected = () => {
			rows.forEach((row) => {
				// @ts-ignore
				// eslint-disable-next-line prettier/prettier
				row.style.display = row.dataset.formId === select.value ? '' : 'none';
			});
		};

		select.addEventListener('change', showSelected);

		// WooCommerce enhances the select with select2, which fires a jQuery
		// event as well. Listen for it only if jQuery is there.
		// @ts-ignore
		if (window.jQuery) {
			// @ts-ignore
			window.jQuery(select).on('change', showSelected);
		}

		showSelected();
	}

	if (password) {
		const toggle = document.createElement('button');

		toggle.type = 'button';
		toggle.className = 'button doitgq-toggle-password';
		toggle.textContent = 'Show';
		toggle.addEventListener('click', () => {
			// @ts-ignore
			const hidden = 'password' === password.type;

			// @ts-ignore
			password.type = hidden ? 'text' : 'password';
			toggle.textContent = hidden ? 'Hide' : 'Show';
		});

		password.insertAdjacentElement('afterend', toggle);
	}
};

/**
 * Ask before deleting a form (list screen).
 */
const initFormList = () => {
	document.querySelectorAll('.doitgq-delete-form').forEach((link) => {
		link.addEventListener('click', (event) => {
			if (!window.confirm('Delete this form?')) {
				event.preventDefault();
			}
		});
	});
};

/**
 * Form editor (WooCommerce > Get a Quote > Forms): add and remove fields,
 * switch the placeholder and options inputs on and off by type, and check the
 * form before it is sent. The server checks it again.
 */
const initFormEditor = () => {
	const table = document.getElementById('doitgq-fields-table');
	const body = document.getElementById('doitgq-fields-body');
	const template = document.getElementById('doitgq-field-row-template');
	const saveButton = document.getElementById('doitgq-save-form');
	const errorBox = document.getElementById('doitgq-editor-error');

	if (!table || !body || !template || !saveButton) {
		return;
	}

	// @ts-ignore
	const list = (name) => (table.dataset[name] || '').split(',').filter(Boolean);
	const optionTypes = list('optionTypes');
	const placeholderTypes = list('placeholderTypes');
	const reservedIds = list('reservedIds');
	// @ts-ignore
	const cleanId = (value) => value.toLowerCase().replace(/[^a-z0-9_-]/g, '');
	let counter = body.querySelectorAll('tr').length;

	// @ts-ignore
	const syncRow = (row) => {
		const type = row.querySelector('select[name$="[type]"]').value;

		// @ts-ignore
		row.querySelector('input[name$="[placeholder]"]').disabled = !placeholderTypes.includes(type);
		// @ts-ignore
		row.querySelector('input[name$="[options]"]').disabled = !optionTypes.includes(type);
	};

	const validate = () => {
		const seen = new Set();
		let hasEmail = false;
		let hasName = false;

		for (const row of body.querySelectorAll('tr')) {
			// @ts-ignore
			const id = cleanId(row.querySelector('input[name$="[id]"]').value);
			// @ts-ignore
			const label = row.querySelector('input[name$="[label]"]').value.trim();
			// @ts-ignore
			const type = row.querySelector('select[name$="[type]"]').value;

			if ('' === id || '' === label) {
				continue;
			}

			// @ts-ignore
			if (reservedIds.includes(id)) {
				return `The field ID "${id}" is reserved. Use another one.`;
			}

			if (seen.has(id)) {
				return `The field ID "${id}" is used twice.`;
			}

			seen.add(id);
			hasEmail = hasEmail || 'email' === type;
			hasName = hasName || ('text' === type && id.includes('name'));
		}

		if (0 === seen.size) {
			return 'Add at least one field.';
		}

		if (!hasEmail) {
			return 'The form needs an Email field.';
		}

		if (!hasName) {
			return 'The form needs a Text field whose ID contains "name", for example user_name.';
		}

		return '';
	};

	body.querySelectorAll('tr').forEach(syncRow);

	// @ts-ignore
	document.getElementById('doitgq-add-field').addEventListener('click', () => {
		body.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, String(counter++)));
		syncRow(body.lastElementChild);
	});

	body.addEventListener('click', (event) => {
		// @ts-ignore
		if (event.target.classList.contains('doitgq-remove-field')) {
			// @ts-ignore
			event.target.closest('tr').remove();
		}
	});

	body.addEventListener('change', (event) => {
		// @ts-ignore
		if (event.target.matches('select[name$="[type]"]')) {
			// @ts-ignore
			syncRow(event.target.closest('tr'));
		}
	});

	saveButton.addEventListener('click', (event) => {
		const message = validate();

		// @ts-ignore
		errorBox.textContent = message;
		// @ts-ignore
		errorBox.hidden = '' === message;

		if (message) {
			event.preventDefault();
		}
	});

};

/**
 * Run a function once the DOM is ready.
 *
 * @param {Function} fn Function to run.
 */
const ready = (fn) => {
	if ('loading' === document.readyState) {
		// @ts-ignore
		document.addEventListener('DOMContentLoaded', fn);
	} else {
		fn();
	}
};

ready(() => {
	initSettingsTab();
	initReplyForm();
	initFormEditor();
	initFormList();
	// Place your administration-specific JavaScript here
});
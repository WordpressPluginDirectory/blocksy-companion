import ctEvents from 'ct-events'
import { registerDynamicChunk } from 'blocksy-frontend'
import { handleAccountModal, activateScreen } from './frontend/account'

let maybeTemplate = ''

const turnstileSelectors = {
	login: '.ct-account-modal #loginform .cf-turnstile',
	lostpassword: '.ct-account-modal #lostpasswordform .cf-turnstile',
	register: '.ct-account-modal #registerform .cf-turnstile',
}

const removeTurnstile = () => {
	if (!window.turnstile) {
		return
	}

	Object.keys(turnstileSelectors).forEach((selector) => {
		const element = document.querySelector(turnstileSelectors[selector])

		if (element) {
			turnstile.remove(turnstileSelectors[selector])

			if (selector === 'register') {
				turnstile.remove(
					'.ct-account-modal #registerform .sct-woocommerce-register'
				)
			}
		}
	})
}

const resetTurnstile = () => {
	if (!window.turnstile) {
		return
	}

	Object.keys(turnstileSelectors).forEach((selector) => {
		const element = document.querySelector(turnstileSelectors[selector])

		if (element) {
			turnstile.render(turnstileSelectors[selector])
		}
	})
}

const integrations = () => {
	if (window.anr_onloadCallback) {
		window.anr_onloadCallback()
	}

	resetTurnstile()

	if (window.Dokan_Vendor_Registration) {
		window.Dokan_Vendor_Registration.init()
	}
}

registerDynamicChunk('blocksy_account', {
	mount: (el, { event }) => {
		// Don't do anything if there's a panel opened already.
		// This means the account is placed in another panel and is opened from
		// it. Soon another click event will be fired on the same element
		// by the panel logic itself.
		if (document.body.dataset.panel) {
			return
		}

		if (!maybeTemplate) {
			let maybeAccount = document.querySelector('#account-modal')

			if (!maybeAccount) {
				location = document.querySelector(
					'[data-id="account"] .ct-account-item'
				)
					? document.querySelector(
							'[data-id="account"] .ct-account-item'
					  ).href
					: el.href

				return
			}

			maybeTemplate = maybeAccount.outerHTML
			removeTurnstile()
			maybeAccount.remove()
		}

		let panel = document.querySelector('#account-modal')
		if (!panel) {
			document
				.querySelector('.ct-drawer-canvas')
				.insertAdjacentHTML('beforeend', maybeTemplate)
			panel = document.querySelector('.ct-drawer-canvas').lastChild
		}

		const actuallyOpen = () => {
			handleAccountModal(panel)

			activateScreen(panel, {
				screen: el.dataset.view || 'login',
			})

			integrations()

			ctEvents.trigger('ct:overlay:handle-click', {
				e: event,
				href: '#account-modal',
				options: {
					isModal: true,
					computeScrollContainer: () => {
						if (!panel.closest('body')) {
							return
						}

						return panel.querySelector('.ct-account-forms')
					},
				},
			})
		}

		actuallyOpen()
	},
})

ctEvents.on('ct:modal:closed', (modalContainer) => {
	if (!modalContainer.closest('#account-modal')) {
		return
	}

	modalContainer.remove()
})

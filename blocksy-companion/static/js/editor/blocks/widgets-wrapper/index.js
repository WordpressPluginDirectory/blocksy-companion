import { createElement } from '@wordpress/element'

import { __ } from 'ct-i18n'
import { registerBlockType } from '@wordpress/blocks'

import { InnerBlocks } from '@wordpress/block-editor'

import Edit from './Edit'

import { addFilter } from '@wordpress/hooks'

addFilter(
	'blockEditor.__unstableCanInsertBlockType',
	'blocksy/widgets-wrapper',
	(canInsert, blockType, rootClientId, { getBlock }) => {
		if (blockType.name.indexOf('blocksy/') !== 0) {
			return canInsert
		}

		if (
			!blockType.parent ||
			!blockType.parent.includes('blocksy/widgets-wrapper')
		) {
			return canInsert
		}

		const parent = getBlock(rootClientId)

		if (parent && parent.name === 'blocksy/widgets-wrapper') {
			return false
		}

		return canInsert
	},
	500
)

registerBlockType('blocksy/widgets-wrapper', {
	apiVersion: 3,
	title: __('Widgets Wrapper', 'blocksy-companion'),
	icon: {
		src: (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 24 24"
				className="wc-block-editor-components-block-icon">
				<path d="M20 14.6c-.2-.3-.5-.6-.9-.8.1-1.2-.1-2.4-.6-3.5s-1.3-2.1-2.3-2.9c-.4-.3-.8-.5-1.3-.8 0-.6-.2-1.2-.5-1.6-.3-.5-.8-.9-1.4-1.1-1-.4-2.4-.1-3.2.8-.5.5-.8 1.2-.8 2-1 .4-1.8 1-2.5 1.8-.8 1-1.4 2.1-1.6 3.3-.1.6-.1 1.3-.1 2-.7.4-1.2 1-1.4 1.7-.3 1.2.1 2.4 1 3.2s2.3.8 3.3.2c.9.6 1.9 1.1 3 1.2.4.1.8.1 1.2.1.9 0 1.9-.2 2.7-.5.5-.2.9-.5 1.4-.8.2.1.4.2.7.3 1.1.3 2.4-.1 3.1-1 .1-.1.2-.3.3-.4v-.1c.7-.9.6-2.2-.1-3.1zm-9.6-8.1c0-.1 0-.2.1-.3 0 0 0-.1.1-.2 0-.1.1-.2.2-.3l.1-.1s.1-.1.1-.2h.1s.1 0 .1-.1c0 0 .1 0 .1-.1h.1c.1 0 .2-.1.3-.1h.7c.1 0 .1 0 .2.1.1 0 .1.1.2.1 0 0 .1 0 .1.1 0 0 .1 0 .1.1l.1.1.2.2v.1l.1.1s0 .1.1.1V7c0 .1 0 .2-.1.3 0 0 0 .1-.1.1 0 0 0 .1-.1.1 0 0-.1.1-.1.2l-.1.1-.1.1h-.1c-.1 0-.1.1-.2.1h-.2c-.1 0-.2.1-.3.1h-1l.1-.1s-.1 0-.1-.1l-.1-.1-.1-.1-.1-.1v-.1l-.1-.1s0-.1-.1-.1v-.1c0-.1-.1-.2-.1-.3V6.6c-.1 0-.1 0-.1-.1zM8 16.6c0 .1-.1.3-.1.4 0 .1-.1.1-.1.2s-.1.1-.1.1l-.1.1-.3.3-.1.1H7v.1c-.1 0-.2.1-.3.1h-.8c-.1 0-.2 0-.3-.1 0 0-.1 0-.1-.1 0 0-.1 0-.1-.1l-.1-.1h-.1l-.1-.1-.1-.1v-.1c.1-.1 0-.2-.1-.3v-.2c0-.1 0-.2-.1-.2V16.3c0-.1 0-.2.1-.2 0 0 0-.1.1-.2 0-.1.1-.2.1-.2l.1-.1.2-.2.1-.1c.1 0 .1-.1.2-.1h.2c.1 0 .2-.1.3-.1h.6c.1 0 .1 0 .2.1l.3-.2s.1 0 .1.1l.2.2c0 .1.1.1.1.1 0 .1.1.1.1.2v.1c.1.2.2.3.2.5v.4c0-.1 0 0 0 0zm7.1 1.4c-.3.2-.5.3-.8.4h-.1l-.2.1c-.1 0-.3.1-.4.1-.3.1-.5.1-.8.2H11.1l-.2-.1c-.1 0-.3-.1-.4-.1-.3-.1-.5-.2-.8-.3-.1-.1-.2-.1-.3-.2-.1-.1-.3-.1-.4-.2 0 .2 0 .1-.1.1.5-.8.6-1.9.3-2.7-.3-.6-.7-1.1-1.2-1.4-.5-.3-1.1-.5-1.7-.4v-1c0-.3.1-.5.2-.8 0-.1.1-.3.1-.4l.1-.3c.1-.2.2-.5.4-.7.1-.1.1-.2.2-.3l.1-.1.1-.1c.1-.4.3-.6.5-.8l.3-.3.1-.1.1-.1c.2-.1.5-.3.7-.4 0 0 .1 0 .1-.1 0 0 0 .1.1.1.3.5.7.9 1.3 1.2.6.3 1.2.4 1.9.3.9-.1 1.7-.8 2.2-1.6.2.1.4.3.7.5l.2.1c.1.1.2.2.3.2.2.2.4.4.5.6l.1.1s0 .1.1.1c.1.1.2.2.2.3.1.2.3.4.4.7 0 .1.1.1.1.2v.1c0 .1.1.2.1.4.1.3.1.5.2.8v1.3c-.4 0-.8 0-1.1.1-1.2.4-2.1 1.6-2 2.9 0 .6.2 1.1.5 1.6zm1.7-3.3zm2.4 1.8v.2c0 .1 0 .2-.1.3v.1c0 .1 0 .1-.1.1 0 0-.1.1-.1.2l-.2.2-.1.1c-.1.1-.2.1-.3.2h-.1c-.1 0-.2.1-.3.1h-.7c-.1 0-.2-.1-.3-.2h-.1l-.1-.1-.1-.1c-.1-.1-.1-.2-.2-.3v-.2c0-.1-.1-.2-.1-.4v-.4c0-.1 0-.2.1-.3l.1-.2v-.1c0-.1.1-.2.2-.3l.1-.1.1-.1c.1-.1.2-.1.3-.2h.2c.1 0 .2-.1.3-.1H18.7c.1 0 .2.1.3.2l.1.1.2.2c.1.1.1.2.2.3v.2c0 .1.1.2.1.3v.1c-.4 0-.4.1-.4.2z" />
			</svg>
		),
	},
	category: 'blocksy-blocks',
	isHidden: true,
	edit: Edit,
	save: () => <InnerBlocks.Content />,
	attributes: {
		heading: {
			type: 'string',
			default: __('Socials', 'blocksy-companion'),
		},
		block: {
			type: 'string',
			default: 'blocksy/socials',
		},
		hasDescription: {
			type: 'boolean',
			default: false,
		},
		description: {
			type: 'string',
			default: '',
		},
		blockAttrs: {
			type: 'object',
			default: {},
		},
		isCollapsible: {
			type: 'boolean',
			default: false,
		},
		defaultExpanded: {
			type: 'boolean',
			default: true,
		},
	},
	supports: {
		className: false,
		spacing: {
			margin: true,
			__experimentalDefaultControls: {
				margin: true,
			},
		},
	},
	variations: [],
})

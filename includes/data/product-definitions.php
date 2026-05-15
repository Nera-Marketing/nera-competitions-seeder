<?php
/**
 * Demo product definitions.
 *
 * Each entry describes one product to seed. The "variant" key drives the
 * variant-specific configuration applied by GG_Demo_Seeder::seed_one().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	array(
		'slug'        => 'cash-prize-random',
		'title'       => '£10,000 Cash Prize — Random Ticket Draw',
		'variant'     => 'standard_random',
		'price'       => 2.99,
		'min_tickets' => 100,
		'max_tickets' => 5000,
		'image_seed'  => 'cash-prize-random',
		'short'       => 'Win £10,000 in cash. Tickets just £2.99. Winner drawn live one year from now.',
		'description' => '<p><strong>£10,000 in cold, hard cash could be yours.</strong></p>'
			. '<p>Enter this headline draw for a chance to walk away with ten grand. Every ticket is assigned a unique random number at checkout — no need to pick, the system does it for you. The more tickets you buy, the more chances you have.</p>'
			. '<h3>How it works</h3>'
			. '<ul><li>Buy as many tickets as you like (up to the per-user cap).</li>'
			. '<li>Each ticket is assigned a randomly generated number.</li>'
			. '<li>One lucky ticket is drawn live at the end date.</li>'
			. '<li>The winner is announced on the site and contacted by email within 24 hours.</li></ul>'
			. '<p>Good luck!</p>',
	),

	array(
		'slug'        => 'tesla-model-y-sequential',
		'title'       => 'Tesla Model Y — Sequential Tickets',
		'variant'     => 'standard_sequential',
		'price'       => 4.99,
		'min_tickets' => 500,
		'max_tickets' => 15000,
		'image_seed'  => 'tesla-model-y',
		'short'       => 'Win a brand-new Tesla Model Y Long Range. Sequentially numbered tickets — first come, first served.',
		'description' => '<p><strong>Drive away in a brand-new Tesla Model Y Long Range.</strong></p>'
			. '<p>This dream-car draw uses <em>sequentially numbered tickets</em>, meaning the first buyer gets ticket #1, the next gets #2, and so on. Buy early to secure a low number — many entrants believe lower numbers bring better luck.</p>'
			. '<h3>The prize</h3>'
			. '<ul><li>Tesla Model Y Long Range, 5-seat configuration</li>'
			. '<li>Pearl White exterior, black interior</li>'
			. '<li>UK delivery included, fully registered and insured for the first year</li>'
			. '<li>Cash alternative of £45,000 available on request</li></ul>',
	),

	array(
		'slug'        => 'ps5-bundle-shuffled',
		'title'       => 'PlayStation 5 Bundle — Shuffled Tickets',
		'variant'     => 'standard_shuffled',
		'price'       => 1.99,
		'min_tickets' => 50,
		'max_tickets' => 2000,
		'image_seed'  => 'ps5-bundle',
		'short'       => 'PS5 console + 3 top games + extra DualSense controller. Shuffled ticket numbers — fully random allocation.',
		'description' => '<p><strong>The ultimate next-gen gaming bundle.</strong></p>'
			. '<p>Win a PlayStation 5 (disc edition) plus three AAA games of your choice and a second DualSense controller. Tickets are assigned using shuffled numbering — your ticket number is randomised within the available pool, so no buyer can predict what they will get.</p>'
			. '<h3>Bundle contents</h3>'
			. '<ul><li>PlayStation 5 console (disc edition)</li>'
			. '<li>Choice of 3 launch-title games</li>'
			. '<li>Additional DualSense wireless controller</li>'
			. '<li>3 months PlayStation Plus Premium</li></ul>',
	),

	array(
		'slug'         => 'iphone-16-pro-instant',
		'title'        => 'iPhone 16 Pro — Instant Win Tickets',
		'variant'      => 'instant_win',
		'price'        => 3.49,
		'min_tickets'  => 100,
		'max_tickets'  => 3000,
		'image_seed'   => 'iphone-16-pro',
		'short'        => 'Buy a ticket, win INSTANTLY. Three guaranteed instant prizes plus the main iPhone 16 Pro draw.',
		'description'  => '<p><strong>Instant wins on selected tickets — plus the main prize!</strong></p>'
			. '<p>This is the only competition where you might win <em>before the draw even closes</em>. Three lucky tickets carry guaranteed instant prizes; pull one of them and your prize is awarded immediately at checkout.</p>'
			. '<h3>Instant prizes (on selected ticket numbers)</h3>'
			. '<ul><li>£100 site credit voucher</li>'
			. '<li>AirPods Pro 2 (delivered free)</li>'
			. '<li>£500 cash prize</li></ul>'
			. '<h3>Main prize</h3>'
			. '<p>Apple iPhone 16 Pro 256GB in Desert Titanium, drawn at the end date from all entered tickets.</p>',
		'instant_rules' => array(
			array(
				'lty_ticket_number'        => '7',
				'lty_prize_type'           => 'physical',
				'lty_instant_winner_prize' => '£100 site credit voucher',
				'lty_prize_amount'         => '100',
			),
			array(
				'lty_ticket_number'        => '42',
				'lty_prize_type'           => 'physical',
				'lty_instant_winner_prize' => 'AirPods Pro 2 (free UK delivery)',
				'lty_prize_amount'         => '249',
			),
			array(
				'lty_ticket_number'        => '88',
				'lty_prize_type'           => 'physical',
				'lty_instant_winner_prize' => '£500 cash prize',
				'lty_prize_amount'         => '500',
			),
		),
	),

	array(
		'slug'        => 'macbook-pro-pick-your-own',
		'title'       => 'MacBook Pro 14" M4 — Pick Your Own Ticket',
		'variant'     => 'manual_pick',
		'price'       => 5.99,
		'min_tickets' => 100,
		'max_tickets' => 2500,
		'image_seed'  => 'macbook-pro',
		'short'       => 'Choose your own lucky ticket number. Win a MacBook Pro 14" with M4 chip.',
		'description' => '<p><strong>You pick the ticket. You pick the luck.</strong></p>'
			. '<p>Unlike our other draws, this one lets you hand-pick your ticket number from those still available. Got a lucky number? A favourite date? Grab it before someone else does.</p>'
			. '<h3>The prize</h3>'
			. '<ul><li>Apple MacBook Pro 14" with M4 chip</li>'
			. '<li>16GB unified memory, 512GB SSD</li>'
			. '<li>Space Black, sealed in box</li>'
			. '<li>Apple 1-year warranty included</li></ul>'
			. '<p>Tickets are released on a first-come, first-served basis. Once a number is taken, it is gone.</p>',
	),

	array(
		'slug'        => 'rolex-submariner-skill',
		'title'       => 'Rolex Submariner — Skill Question Required',
		'variant'     => 'skill_qa',
		'price'       => 9.99,
		'min_tickets' => 500,
		'max_tickets' => 10000,
		'image_seed'  => 'rolex-submariner',
		'short'       => 'Win a Rolex Submariner worth £10,000+. Answer the skill question correctly to enter.',
		'description' => '<p><strong>An icon of horology could be on your wrist.</strong></p>'
			. '<p>Win a brand-new Rolex Submariner Date in stainless steel — the most recognisable luxury sports watch ever made. UK price tag is north of £10,000, but it could be yours for the price of a ticket.</p>'
			. '<p>To enter, you must <strong>correctly answer the skill question</strong> shown on the product page. This is a competition of skill, not pure chance.</p>'
			. '<h3>The prize</h3>'
			. '<ul><li>Rolex Submariner Date 126610LN</li>'
			. '<li>Oystersteel case, black dial, black Cerachrom bezel</li>'
			. '<li>Brand new, full box and papers</li>'
			. '<li>Authenticity verified by an independent watchmaker</li></ul>',
		'questions'   => array(
			array(
				'question' => 'In which Swiss city is Rolex headquartered?',
				'answers'  => array(
					array( 'label' => 'Geneva',  'valid' => 'yes' ),
					array( 'label' => 'Zurich',  'valid' => '' ),
					array( 'label' => 'Bern',    'valid' => '' ),
					array( 'label' => 'Lausanne', 'valid' => '' ),
				),
			),
		),
	),

	array(
		'slug'        => 'bmw-m4-ended',
		'title'       => '[ENDED] BMW M4 Competition — Draw Completed',
		'variant'     => 'ended',
		'price'       => 7.99,
		'min_tickets' => 1000,
		'max_tickets' => 20000,
		'image_seed'  => 'bmw-m4',
		'short'       => 'This draw has closed. Winner announced and contacted. Example of an ended competition.',
		'description' => '<p><strong>This competition has now ended.</strong></p>'
			. '<p>The BMW M4 Competition draw closed last week. The winning ticket has been drawn and the lucky winner contacted directly. Thank you to everyone who entered — keep an eye on our active competitions for more chances to win supercar-grade prizes.</p>'
			. '<h3>The prize that was won</h3>'
			. '<ul><li>BMW M4 Competition Coupé, 510hp twin-turbo inline six</li>'
			. '<li>Sao Paulo Yellow with carbon-fibre interior package</li>'
			. '<li>UK-registered, delivery and first year of insurance included</li></ul>'
			. '<p>This product page is preserved as a record of past competitions.</p>',
	),
);

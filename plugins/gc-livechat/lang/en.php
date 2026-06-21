<?php

declare(strict_types=1);

/**
 * GC Live Chat — English strings (the plugin's OWN pack).
 */
return [
    // ── Frontend widget ──
    'front.title'           => 'Chat with us',
    'front.subtitle'        => "We're here to help",
    'front.greeting'        => "Hi! 👋 How can I help you today?",
    'front.greeting_human'  => 'Hi! Leave your message and a team member will reply shortly.',
    'front.placeholder'     => 'Type your message…',
    'front.send'            => 'Send',
    'front.talk_human'      => 'Talk to a human',
    'front.connecting'      => 'Connecting you to an operator…',
    'front.operator_joined' => 'An operator has joined the chat.',
    'front.ai_unavailable'  => "I couldn't reach the assistant. An operator will help you shortly.",
    'front.error'           => 'Something went wrong. Please try again.',

    // ── Admin ──
    'admin.inbox_title'     => 'Live Chat',
    'admin.settings_title'  => 'Live Chat — Settings',
    'admin.tab_inbox'       => 'Inbox',
    'admin.tab_settings'    => 'Settings',
    'admin.open_inbox'      => 'Open inbox',
    'admin.save'            => 'Save settings',
    'admin.saved'           => 'Settings saved.',

    'admin.no_chats'        => 'No conversations yet.',
    'admin.pick_chat'       => 'Select a conversation to view it.',
    'admin.request'         => 'Request',
    'admin.take_over'       => 'Take over',
    'admin.taken_over'      => 'You joined the conversation.',
    'admin.close_chat'      => 'Close',
    'admin.close_confirm'   => 'Close this conversation?',
    'admin.closed'          => 'Conversation closed.',
    'admin.reply_ph'        => 'Type your reply…',
    'admin.op_joined'       => ':name joined the chat.',

    'admin.s_general'       => 'General',
    'admin.s_enabled'       => 'Enable the chat widget on the site',
    'admin.s_title'         => 'Widget title',
    'admin.s_color'         => 'Accent color',
    'admin.s_greeting'      => 'Greeting message',
    'admin.s_ai'            => 'AI assistant',
    'admin.s_active'        => 'Active',
    'admin.s_not_set'       => 'No API key',
    'admin.s_provider'      => 'AI provider',
    'admin.s_model'         => 'Model',
    'admin.s_apikey'        => 'API key',
    'admin.s_key_saved'     => '(saved)',
    'admin.s_key_ph'        => 'Paste your API key',
    'admin.s_key_hint'      => 'Stored server-side and never exposed to visitors. Leave blank to keep the current key.',
    'admin.s_use_content'   => 'Let the AI reference my site content (pages & posts)',
    'admin.s_knowledge'     => 'Knowledge & instructions',
    'admin.s_instructions'  => 'Custom instructions',
    'admin.s_instructions_ph' => 'e.g. Our support hours are 9–18, Mon–Fri. Be polite and concise.',
    'admin.s_instructions_hint' => 'Tells the AI how to behave and any business specifics.',
    'admin.s_faq'           => 'FAQ / knowledge base',
    'admin.s_faq_ph'        => "Q: Do you ship abroad?\nA: Yes, worldwide within 5–7 days.",
];

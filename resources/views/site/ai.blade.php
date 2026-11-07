@extends('site.layout', ['title' => 'AI Search', 'active' => 'search'])

@section('content')
<section
    class="ai-page wrap"
    data-reveal
    x-data="aiSearch({
        chatUrl: @js($chatUrl),
        csrf: @js(csrf_token()),
        enabled: @js($enabled),
        prompt: @js($prompt),
        helper: @js($helper),
        voiceInput: @js($voiceInput),
        moodboardOn: @js($moodboardOn),
        moodboardLocked: @js($moodboardLocked),
        cities: @js($cities),
        seed: @js($seed),
        vendorUrl: @js(url('/creatives')),
    })"
>
    <div class="ai-page-bar">
        <a class="ghost-btn" href="{{ route('site.home') }}#ai-search" style="margin:0">← Home</a>
        <h1>✨ AI Search</h1>
        <div class="ai-page-bar-actions">
            <span class="ai-provider" x-text="provider.toUpperCase()"></span>
            <button type="button" class="ghost-btn" style="margin:0" @click="reset()" title="New chat">New</button>
        </div>
    </div>

    <div class="ai-stepper" aria-label="Assistant steps">
        <template x-for="(step, index) in steps" :key="step">
            <div class="ai-step-wrap">
                <span class="ai-step-line" x-show="index > 0" :class="slotFilled(steps[index - 1]) && 'is-on'"></span>
                <div class="ai-step" :class="{ 'is-on': slotFilled(step), 'is-current': ask === step }">
                    <i></i>
                    <small x-text="step[0].toUpperCase() + step.slice(1)"></small>
                </div>
            </div>
        </template>
    </div>

    @unless($enabled)
        <div class="flash-err">AI assistant is temporarily offline — use classic search instead.</div>
    @endunless

    <div class="ai-hero">
        <p class="ai-kicker">LENS ASSISTANT</p>
        <h2 x-text="prompt"></h2>
        <p class="lede" x-text="helper"></p>
        <div class="ai-tags">
            <span>Chat</span>
            <span x-show="voiceInput">Voice</span>
            <span>Compare</span>
            <span x-show="moodboardOn && !moodboardLocked">Moodboard</span>
        </div>
        <div class="ai-suggests" x-show="lines.length <= 1">
            <button type="button" @click="send('Wedding photographer next month, mid budget')">Wedding photographer next month, mid budget</button>
            <button type="button" @click="send('Food photoshoot in Cairo')">Food photoshoot in Cairo</button>
            <button type="button" @click="send('Studio for a half day this weekend')">Studio for a half day this weekend</button>
        </div>
    </div>

    <div class="ai-thread" x-ref="thread">
        <template x-for="(line, index) in lines" :key="index">
            <div class="ai-bubble" :class="line.role === 'user' ? 'is-mine' : 'is-bot'">
                <p x-text="line.body"></p>
            </div>
        </template>
        <div class="ai-busy" x-show="busy" x-cloak></div>

        <template x-if="status === 'ready' && pick">
            <div class="ai-pick">
                <small>MY PICK</small>
                <strong x-text="pick.display_name || 'Creator'"></strong>
                <p x-text="pick.why || ''"></p>
            </div>
        </template>

        <template x-if="status === 'ready' && comparison.length">
            <div class="ai-compare">
                <h3>Comparison</h3>
                <template x-for="row in comparison" :key="row.id || row.rank">
                    <div class="ai-compare-row">
                        <span class="rank" x-text="row.rank"></span>
                        <strong x-text="row.display_name"></strong>
                        <small x-text="row.city || ''"></small>
                    </div>
                </template>
            </div>
        </template>

        <template x-if="status === 'ready' && vendors.length">
            <div class="ai-vendors">
                <template x-for="vendor in vendors" :key="vendor.id">
                    <a class="ai-vendor" :href="vendor.url || (vendorUrl + '/' + vendor.id)">
                        <span class="ai-vendor-avatar" x-text="initials(vendor.display_name)"></span>
                        <span>
                            <strong x-text="vendor.display_name"></strong>
                            <small x-text="[vendor.vendor_type, vendor.city].filter(Boolean).join(' · ')"></small>
                        </span>
                        <span class="ai-vendor-go">→</span>
                    </a>
                </template>
            </div>
        </template>

        <template x-if="status === 'ready' && addOns.length">
            <div class="ai-addons">
                <h3>Add-ons</h3>
                <template x-for="(item, i) in addOns" :key="i">
                    <p x-text="(item.name || item.vendor_type_name || 'Add-on') + ' — ' + (item.message || '')"></p>
                </template>
            </div>
        </template>

        <template x-if="status === 'ready' && moodboardOn">
            <div class="ai-mood" :class="moodboardLocked && 'is-locked'">
                <strong x-text="moodboardLocked ? 'Moodboard locked' : 'Moodboard ready'"></strong>
                <p x-text="moodboardLocked ? 'Moodboard and shoot script unlock after payment.' : 'Moodboard can be generated from this brief when you book.'"></p>
            </div>
        </template>
    </div>

    <div class="ai-quick" x-show="quickReplies.length" x-cloak>
        <template x-for="chip in quickReplies" :key="chip">
            <button type="button" class="book-chip" @click="send(chip)" x-text="chip"></button>
        </template>
    </div>

    <form class="ai-composer" @submit.prevent="send()">
        <input type="text" x-model="draft" :disabled="!enabled || busy" placeholder="Describe the shoot, city, date, or budget…" autocomplete="off">
        <button class="ai-btn" type="submit" :disabled="!enabled || busy || !draft.trim()" x-text="busy ? '…' : 'Send'"></button>
    </form>
</section>
@endsection

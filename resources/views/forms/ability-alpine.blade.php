{{-- The state and the verbs the matrix works with. It lives apart from the view because
     an expression this long inside an attribute is unreadable, not because anything else
     shares it. --}}
state: $wire.$entangle('{{ $getStatePath() }}'),
disabled: @js($disabled),
tab: @js(array_key_first($sections)),
order: @js([$getNeutral(), 'granted', 'forbidden']),
labels: @js($stances),
at(subject, action) {
    return this.state?.[subject]?.[action] ?? @js($getNeutral())
},
set(subject, action, stance) {
    if (this.disabled) {
        return
    }

    if (! this.state[subject]) {
        this.state[subject] = {}
    }

    this.state[subject][action] = stance
},
cycle(subject, action, backwards) {
    if (this.disabled) {
        return
    }

    const from = this.order.indexOf(this.at(subject, action))
    const step = backwards ? -1 : 1

    this.set(subject, action, this.order[(from + step + this.order.length) % this.order.length])
},
says(subject, action) {
    return this.labels[this.at(subject, action)] ?? this.at(subject, action)
},
{{-- Only while the cell is still unmarked: the moment somebody puts a stance on it, the
     broader rule stops being what decides. --}}
inherits(subject, action, reached) {
    return reached && this.at(subject, action) === @js($getNeutral())
},
{{-- A shortcut leaves the row saying exactly what it names: it grants its own and
     silences the rest. Adding without taking away would turn "read only" into "reading on
     top of whatever was already there", which is what its name promises it will not do. --}}
apply(subject, actions, offered) {
    for (const action of offered) {
        this.set(subject, action, actions.includes(action) ? 'granted' : @js($getNeutral()))
    }
},
clear(subject, offered) {
    this.apply(subject, [], offered)
},
granted(subject) {
    return Object.values(this.state?.[subject] ?? {}).filter((s) => s === 'granted').length
},
forbidden(subject) {
    return Object.values(this.state?.[subject] ?? {}).filter((s) => s === 'forbidden').length
},
{{-- The count is only known here, and the words for one and for many are only known
     there, so both forms come along and this picks. --}}
words: {
    granted: { one: @js(trans_choice('filament-bouncer::roles.summary.granted', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.granted', 2, ['count' => '%n'])) },
    forbidden: { one: @js(trans_choice('filament-bouncer::roles.summary.forbidden', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.forbidden', 2, ['count' => '%n'])) },
    neutral: { one: @js(trans_choice('filament-bouncer::roles.summary.neutral', 1)), many: @js(trans_choice('filament-bouncer::roles.summary.neutral', 2, ['count' => '%n'])) },
    badge: { one: @js(trans_choice('filament-bouncer::roles.form.forbidden_count', 1)), many: @js(trans_choice('filament-bouncer::roles.form.forbidden_count', 2, ['count' => '%n'])) },
},
say(kind, count) {
    return count === 1 ? this.words[kind].one : this.words[kind].many.replace('%n', count)
},
{{-- What a tab has to show on itself: a group is written along with the three others in
     one save, so without this a role reaching a dangerous page reads as harmless from
     whichever tab happens to be open. --}}
grantedIn(keys) {
    return keys.reduce((total, key) => total + this.granted(key), 0)
},
tallyIn(keys) {
    const total = keys.reduce((sum, key) => sum + Object.keys(this.state?.[key] ?? {}).length, 0)

    return this.grantedIn(keys) + ' / ' + total
},
tally() {
    let granted = 0, forbidden = 0, neutral = 0

    for (const actions of Object.values(this.state ?? {})) {
        for (const stance of Object.values(actions ?? {})) {
            if (stance === 'granted') { granted++ }
            else if (stance === 'forbidden') { forbidden++ }
            else { neutral++ }
        }
    }

    return { granted, forbidden, neutral }
},

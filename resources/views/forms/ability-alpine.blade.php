{{-- The state and the verbs the matrix works with. It lives apart from the view because
     an expression this long inside an attribute is unreadable, not because anything else
     shares it. --}}
state: $wire.$entangle('{{ $getStatePath() }}'),
disabled: @js($disabled),
tab: @js(array_key_first($sections)),
order: @js([$getNeutral(), 'granted', 'forbidden']),
labels: @js($stances),
at(entity, action) {
    return this.state?.[entity]?.[action] ?? @js($getNeutral())
},
set(entity, action, stance) {
    if (this.disabled) {
        return
    }

    if (! this.state[entity]) {
        this.state[entity] = {}
    }

    this.state[entity][action] = stance
},
cycle(entity, action, backwards) {
    if (this.disabled) {
        return
    }

    const from = this.order.indexOf(this.at(entity, action))
    const step = backwards ? -1 : 1

    this.set(entity, action, this.order[(from + step + this.order.length) % this.order.length])
},
says(entity, action) {
    return this.labels[this.at(entity, action)] ?? this.at(entity, action)
},
{{-- Only while the cell is still unmarked: the moment somebody puts a stance on it, the
     broader rule stops being what decides.

     `reached` is what the role already holds, worked out on the server. The manage box is
     read here as well and live, because granting a whole model is a rule composed on this
     very screen: it stores one row, `*` over that model, and never the columns beside it —
     so without this those columns stay blank until the form is saved and opened again,
     which is exactly when it is too late to check what was about to be handed out. --}}
inherits(entity, action, reached) {
    if (this.at(entity, action) !== @js($getNeutral())) {
        return false
    }

    return reached || (action !== @js($columns['manage']['action']) && this.at(entity, @js($columns['manage']['action'])) === 'granted')
},
{{-- A shortcut leaves the row saying exactly what it names: it grants its own and
     silences the rest. Adding without taking away would turn "read only" into "reading on
     top of whatever was already there", which is what its name promises it will not do. --}}
apply(entity, actions, offered) {
    for (const action of offered) {
        this.set(entity, action, actions.includes(action) ? 'granted' : @js($getNeutral()))
    }
},
clear(entity, offered) {
    this.apply(entity, [], offered)
},
{{-- What a tab has to show on itself: a group is written along with the three others in
     one save, so without this a role reaching a dangerous page reads as harmless from
     whichever tab happens to be open. --}}
grantedIn(keys) {
    return keys.reduce((total, key) => total + Object.values(this.state?.[key] ?? {}).filter((s) => s === 'granted').length, 0)
},
tallyIn(keys) {
    const total = keys.reduce((sum, key) => sum + Object.keys(this.state?.[key] ?? {}).length, 0)

    return this.grantedIn(keys) + ' / ' + total
},

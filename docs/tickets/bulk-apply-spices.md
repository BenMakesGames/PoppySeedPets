# Bulk-Apply Spices

## Context
**Current behavior**: Cook/combine currently handles one food item and one spice item at a time. The action is not designed to infer intent across a multi-item batch selection.
**New behavior**: This ticket adds a single, unambiguous batch-spicing flow for matching food and matching spice selections, with rejection and confirmation states when intent is unclear or mixed.

## What will be true

- Selecting multiple food items and multiple spice items and using cook/combine can apply each spice to a food in a single action, when the player's intent is unambiguous.
- Bulk-spicing only proceeds when all selected food items are the same type AND all selected spice items are the same type. If the foods aren't all the same, or the spices aren't all the same, no spicing happens — even if the counts would otherwise line up cleanly (for example, six identical unspiced foods with three Spicy Spices and three Onion Powders is ambiguous and nothing gets spiced).
- When all selected food items are the same type, all selected spice items are the same type, and at least as many food items as spice items were selected, every selected spice gets applied to a different one of those food items.
- When all selected food items are the same type, all selected spice items are the same type, and at least as many spice items as food items were selected, every selected food item receives one of the spices.
- If exactly as many food items as spice items were selected, every food item ends up spiced and no spice is left over.
- If more foods were selected than spices, the extra foods remain unspiced and no spices are left over.
- If more spices were selected than foods, every food ends up spiced and the extra spices remain unused.
- After a successful bulk-spicing action, the player sees one of these friendly confirmation messages:
  - Exact match: "All `<number>` of those `<food>` now have the `<spice>` spice! Batch-prepping FTW!"
  - More foods than spices: "`<number>` of those `<food>` now have the `<spice>` spice - but there wasn't enough for the last `<count>` `<food>` so they're plain for now."
  - More spices than foods: "All `<number>` of those `<food>` now have the `<spice>` spice! You have `<count>` `<spice>` spices leftover."
- If the selected food items aren't all the same type, no spicing happens.
- If any food item in the selection already has a spice applied, no spicing happens for the whole selection — even if the rest of the selection would otherwise be unambiguous.
- In every other case where intent can't be determined this way, no spicing happens, and the player sees the message: "Hmm, this is some complicated seasoning you're requesting. Let's not."
- Bulk-spicing works anywhere cook/combine is available today (for example the House and the Basement); it isn't a separate feature or a separate location.

## Out of scope

- Applying one shared spice across a mix of different food types (for example, one Fish, one Egg, and one Chocolate bar all receiving the same spice). All foods in a bulk-spicing selection must be the same type, even when the spices are the ones providing the matching quantity.
- Applying a mix of different spice types across a set of identical foods (for example, three Spicy Spices and three Onion Powders applied across six identical unspiced foods). All spices in a bulk-spicing selection must be the same type too, even when the food count and spice count line up evenly.
- Any new handling for selections that mix in items that are neither food nor spice. The whole action already gets rejected in that case, and that existing behavior is relied on rather than replaced.
- Partially spicing a selection that includes an already-spiced food. No subset of the selection gets spiced in that case; the whole batch is treated as ambiguous.

## Invariants

- Applying a single spice to a single food continues to work exactly as it does today.
- No other cook/combine behavior changes as part of this.
- A food item never ends up with more than one spice applied to it, before or after this feature exists.

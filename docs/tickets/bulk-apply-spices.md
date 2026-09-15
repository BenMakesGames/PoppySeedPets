# Bulk-Apply Spices

## Context
**Existing behavior**: Cook/combine handles one food item and one spice item at a time. That behavior is already implemented and remains unchanged.

**Enhancement**: This ticket adds batch spicing when the relationship between selected foods and spices is unambiguous. A batch is unambiguous when either all selected foods are the same type or all selected spices are the same type, subject to the count rules below. A batch with both mixed food types and mixed spice types remains ambiguous.

## What will be true

- Selecting multiple food items and multiple spice items and using cook/combine can apply each spice to a food in a single action, when the player's intent is unambiguous.
- When selected foods are all the same type, the selected spices may be the same type or different types, provided there are no more spices than foods.
- When all selected food items are the same type and at least as many food items as spice items were selected, every selected spice gets applied to a different one of those food items, regardless of whether the selected spice items are the same or different types.
- When selected spices are all the same type, the selected foods may be the same type or different types, provided there are at least as many spices as foods.
- When all selected spice items are the same type and at least as many spice items as food items were selected, every selected food item receives one of the spices, regardless of whether the selected food items are the same or different types.
- When selected foods are all the same type and selected spices are all the same type, the batch is unambiguous regardless of their relative counts:

  | Selected foods | Selected spices | Result |
  | --- | --- | --- |
  | 4 Oranges | 2 Spicy Spices | Two Oranges receive Spicy Spice; two remain plain. |
  | 4 Oranges | 4 Spicy Spices | Every Orange receives Spicy Spice. |
  | 4 Oranges | 6 Spicy Spices | Every Orange receives Spicy Spice; two Spicy Spices remain unused. |

- If exactly as many food items as spice items were selected, every food item ends up spiced and no spice is left over.
- If more foods were selected than spices and all food items are the same type, the extra foods remain unspiced and no spices are left over.
- If more spices were selected than foods and all spice items are the same type, every food ends up spiced and the extra spices remain unused.
- After a successful bulk-spicing action, the player sees one of these friendly confirmation messages:
  - Exact match: "`<quantity list of foods>` are now seasoned: `<quantity list of applied spices>`! Batch-prepping FTW!"
  - More foods than spices: "`<quantity list of spiced foods>` are now seasoned: `<quantity list of applied spices>` - but there wasn't enough for the last `<quantity list of unspiced foods>` so they're plain for now."
  - More spices than foods: "`<quantity list of foods>` are now seasoned: `<quantity list of applied spices>`! You have `<quantity list of leftover spices>` leftover."
- Every quantity list reuses the existing recipe confirmation formatter.
- Quantity lists report totals by item type and do not identify individual food-to-spice pairings.
- If any food item in the selection already has a spice applied, no spicing happens for the whole selection — even if the rest of the selection would otherwise be unambiguous.
- If the selected food items are different types and the selected spice items are different types, no spicing happens.
- If selected food items are different types and fewer spices than foods are selected, no spicing happens.
- If more spices than foods are selected and the spices are not all the same type, no spicing happens.
- In every other case where intent can't be determined this way, no spicing happens, and the player sees the message: "Hmm, this is some complicated seasoning you're requesting. Let's not."
- Bulk-spicing works anywhere cook/combine is available today (for example the House and the Basement); it isn't a separate feature or a separate location.

## Out of scope

- Any new handling for selections that mix in items that are neither food nor spice. The whole action already gets rejected in that case, and that existing behavior is relied on rather than replaced.
- Partially spicing a selection that includes an already-spiced food. No subset of the selection gets spiced in that case; the whole batch is treated as ambiguous.

## Invariants

- Applying a single spice to a single food continues to work exactly as it does today.
- No other cook/combine behavior changes as part of this.
- A food item never ends up with more than one spice applied to it, before or after this feature exists.

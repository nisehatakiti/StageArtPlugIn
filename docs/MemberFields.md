# Member Fields Specification

StageArtPlugInのメンバー自由記述項目は、劇団全体で共通する項目定義と、各メンバーの入力値を分離して管理する。

## Standard Member Fields

| 項目 | 必須 | 入力方式 |
|---|---:|---|
| 名前 | Yes | text |
| Slug | Yes (generated) | generated/editable |
| 写真 | No | media |
| 役割 | No | multi-select preset |
| プロフィール | No | rich text |
| SNS | No | social URLs |
| 劇団共通自由記述項目 | No | shared custom fields |
| 表示順 | No | reorder |

### Role Presets

- 俳優
- 演出
- 脚本
- 制作
- 音響
- 照明
- 舞台監督
- 劇団代表

Multiple roles may be selected for one member.

## Shared Custom Field Model

A custom field is defined once for the entire theater organization/site. Members store values against that field definition.

```text
Member Field Definition
├─ id
├─ name
├─ description
├─ is_active
└─ display_order

Member Field Value
├─ member_id
├─ field_id
└─ value
```

The field ID remains stable when its name or description is edited.

## Lifecycle

### Add

Add a field from the Member Fields admin screen.

- New field is active by default.
- It receives a display order.
- All members see the field in their edit screen.
- Existing members start with an empty value.
- The field is optional; a blank value is valid.

### Edit

Edit the field definition without changing its internal ID.

Editable properties:

- field name
- description/help text

Changing the name or description does not delete or migrate existing member values.

### Reorder

Drag-and-drop or equivalent ordering changes `display_order` on the shared field definition.

The resulting order is used consistently across all member edit screens and public member pages.

### Deactivate

The normal removal action is deactivation, not deletion.

Confirmation message:

> 「{項目名}」を無効化しますか？
> この項目はメンバーの編集画面および公開ページに表示されなくなります。
> 入力済みのデータは削除されず、保持されます。
> 無効化後は、いつでも再利用できます。

After confirmation:

- `is_active = 0`
- hide the field from member edit UI
- hide the field from public member pages
- keep all member values
- keep the field's previous display order

No member data is deleted.

### Reuse

A deactivated field can be reactivated from the Member Fields admin screen.

Confirmation message:

> 「{項目名}」を再利用しますか？
> 再利用すると、この項目がすべてのメンバーの編集画面および公開ページに再び表示されます。
> 以前入力されていたメンバーのデータもそのまま利用できます。

After confirmation:

- `is_active = 1`
- show the field in member edit UI
- show the field on public member pages when the value is present
- restore previous member values automatically
- retain the field's previous display order

The field can be reordered again after reuse.

### Hard Delete

The initial implementation does not expose normal hard deletion for member custom fields. Deactivation is the standard removal operation.

## Screen Behavior

### Member Fields List

```text
メンバー項目

有効
☰ 趣味                  [編集] [無効化]
☰ 好きな劇作家          [編集] [無効化]
☰ 特技                  [編集] [無効化]
☰ ひとこと              [編集] [無効化]

＋ 項目を追加

無効
  出身地                [編集] [再利用]
  好きな映画            [編集] [再利用]
```

Active fields are shown first; inactive fields are grouped separately below them.

### Member Edit

Only active shared fields are rendered. Inactive fields are omitted from the form, while their stored values remain intact.

### Public Member Page

Only active shared fields are rendered. Inactive fields are never exposed solely because they have historical data.

## Data Safety Rule

The key invariant is:

```text
Field definition lifecycle ≠ Member value lifecycle
```

Changing a field's active state must not destroy member values.

Therefore:

```text
active → inactive → active
```

is reversible, and the member's previous input is restored automatically when the field is reused.

# StageArt PlugIn Architecture

## 1. Starting Point

StageArt PlugIn is a WordPress plugin for a performing-arts organization to build and manage its own public website.

The WordPress site itself is the organization's site context. StageArt PlugIn therefore does not introduce a separate Organization URL or duplicate Organization entity solely to represent the site.

```text
WordPress Site
    ├── Basic organization information
    ├── Contact content
    ├── Members
    ├── Productions
    ├── Notices
    ├── Homepage configuration
    └── Menu configuration
```

The plugin provides a WordPress-native public site structure for a performing-arts organization, while keeping business functions such as performances and ticketing extensible.

## 2. Organization Information

Basic organization information is site-level data/settings rather than an independently addressable Organization content item.

Typical fields include:

- organization name
- organization introduction
- social links such as X, Instagram, YouTube, and Facebook
- logo / organization image where appropriate

There is no separate Organization slug or Organization URL.

## 3. Contact Content

Contact information is provided as system content that can be placed on the site when the organization chooses.

Typical fields include:

- address
- email address
- phone number

Contact content is independent of placement. It may be linked from the menu, shown on the homepage, nested under another menu item, or left unpublished from the site's navigation.

## 4. Production

A Production is independent content with its own slug and public page.

```text
Production
    ├── title
    ├── slug
    ├── overview
    ├── main image
    ├── introduction
    ├── cast / staff
    ├── performance dates
    ├── venue
    └── ticket information
```

Public URLs use the production slug:

```text
/production/{production-slug}/
```

WordPress `post_name` semantics are used. Slugs are editable, and old slugs are retained as redirect history so that previous URLs can permanently redirect to the current slug without redirect chains. Publication state is separate from production lifecycle/date state.

"Past productions" are not a separate data model. They are an archive/listing view of the same Production content based on publication state and date/status.

## 5. Members

Members are an independent content type. Each member is an individual content item with its own public detail page. Members are not authenticated users by default.

The content structure is:

```text
メンバー
├─ メンバー一覧
├─ ○○
├─ ××
└─ △△
```

### 5.1 Member List

「メンバー一覧」は a system-provided list content item.

It lists public member individual content in the configured member display order.

The member list is independent content and can be placed on the homepage or menu like other content.

### 5.2 Individual Member Content

Adding a member creates one individual member content item.

The individual member content:

- has an independent public page
- is addressable through its slug
- can be placed directly on the homepage
- can be linked directly from the menu
- can be referenced from other content such as the theater-company representative greeting

The same member content may be referenced from multiple locations. Member data is not copied into each placement.

This means use cases such as featuring a "看板役者" on the homepage require no special content type. The member's individual content can simply be placed directly on the homepage.

### 5.3 Member Standard Fields

A member may contain:

- name (required)
- slug (required internally; generated automatically and editable)
- profile image
- roles
- profile / biography
- SNS links
- organization-defined common custom fields
- display order
- publication state

### 5.4 Roles

Roles are provided as a built-in multi-select preset rather than free text.

```text
- 俳優
- 演出
- 脚本
- 制作
- 音響
- 照明
- 舞台監督
- 劇団代表
```

A member may select multiple roles.

### 5.5 Organization-wide Custom Member Fields

Custom member fields are defined once for the entire WordPress site / theater organization and shared by all members.

Each field has at least:

- internal field ID
- field name
- description / help text
- active / inactive state
- display order

Each member stores a value against the shared field definition.

Lifecycle rules:

- **Add:** creates a new shared field. It becomes active and is available to all members with an initially empty value.
- **Edit:** changes the field definition, such as field name or help text, without changing the internal ID or existing member values.
- **Reorder:** changes the shared display order for every member.
- **Deactivate:** hides the field from member edit screens and public member pages. Existing member values remain stored.
- **Reuse:** reactivates the field. Existing member values become visible and usable again without re-entry.
- **Hard Delete:** not exposed as a normal initial feature. Deactivation is the standard removal operation.

When a field is deactivated, the confirmation must explain that the field will disappear from editing and public display but that entered data will be retained.

When a field is reused, the confirmation must explain that the field returns for all members and previously entered values will be reused.

A field retains its previous display order while inactive so that reuse can restore that position. It can be reordered again after reuse.

The detailed specification is maintained in `docs/MemberFields.md`.

### 5.6 Member Display Order

Individual members have a display order used by the Member List and other list views based on member order.

Administrators can reorder members through drag-and-drop or an equivalent ordering interface.

Member display order is independent of the member slug and public URL.

### 5.7 Publication State

Each member individual content has a publication state.

Only public members are included in public member lists and public references.

Draft or otherwise non-public members remain in the content model and can be published later.

## 6. Theater Company Representative Greeting

「劇団代表挨拶」は、メンバー個人コンテンツとは別の独立コンテンツです。

The greeting content selects a member as its representative.

```text
劇団代表挨拶
├─ 対象メンバー → 山田太郎
├─ 写真 → 山田太郎から自動取得
├─ 名前 → 山田太郎から自動取得
├─ メンバーページへのリンク → 自動生成
└─ 挨拶本文 → 劇団代表挨拶側で入力
```

The photo, name, and member-page link are resolved from the selected member content. They are not duplicated and manually maintained in the greeting content.

Changing the member's photo or name therefore updates the greeting automatically.

## 7. Homepage and Menu

Content is independent of where it is displayed.

### Homepage

The homepage is a configurable set of sections/slots. A slot may reference content or headings, and sections can be reordered. Theme settings control presentation.

Member individual content and the Member List are both valid content references.

Example:

```text
トップページ
├─ 次回公演
├─ 劇団代表挨拶
├─ 山田太郎
├─ メンバー一覧
└─ お知らせ
```

### Menu

The menu is an independent navigation tree. Items may be folders or links to content/pages, with:

- hierarchy
- label
- enabled/disabled state
- order
- indent/outdent behavior

The same content may be linked from multiple menu locations.

## 8. Authentication and Tickets

Ticket management is the first area that requires authenticated users.

StageArt PlugIn is an AuthCore **Application**, not an Extension. Its plugin header declares:

```text
AuthCore: application
AuthCore Application Key: stageart
AuthCore Application Name: StageArt
```

AuthCore owns authentication, credentials, sessions, password/security flows and account-related infrastructure. StageArt PlugIn owns application-specific business data such as ticket types, performances, reservations, tickets and check-in.

Conceptually:

```text
Visitor
   ↓
Production Page
   ↓
Ticket Selection
   ↓
StageArt / AuthCore Account
   ↓
Reservation
   ↓
Ticket
```

StageArt PlugIn should use a boundary/adapter around AuthCore so business-domain code does not directly depend on AuthCore implementation classes.

## 9. Separation of Responsibilities

```text
AuthCore
 └── authentication / account / credential / session

StageArt PlugIn
 ├── site-level organization information
 ├── contact content
 ├── production
 ├── member
 ├── homepage configuration
 ├── menu configuration
 ├── performance
 ├── ticket type
 ├── reservation
 ├── ticket
 └── check-in
```

StageArt PlugIn must not duplicate password or credential management.

## 10. WordPress Representation

The plugin should use WordPress-native concepts where they fit the content model.

Recommended initial representation:

- site organization information: WordPress/site settings or plugin settings
- contact: system content/page-like content
- production: custom post type with independent `post_name`
- member: dedicated StageArt member content model with individual public detail pages
- member list: system-provided list content referencing public member items
- theater company representative greeting: independent content referencing one member
- notices: WordPress posts or equivalent native publishing content
- performance: StageArt data model related to Production
- ticket / reservation / check-in: dedicated StageArt data models/tables

A separate Organization CPT/table is not required merely to represent the WordPress site's owning theater organization.

## 11. Initial Implementation Order

1. Site-level organization information
2. Contact content
3. Member content, member list, and member common-field management
4. Theater company representative greeting
5. Production content and public production pages
6. Homepage configuration
7. Menu configuration
8. Performance management
9. AuthCore integration boundary
10. StageArt account creation/login flow
11. Ticket types
12. Reservations
13. Tickets / check-in

The first public-site milestone is a usable WordPress theater website with organization information, contact content, members, productions, configurable homepage sections, and menu navigation, while maintaining a clean foundation for authenticated ticket management.

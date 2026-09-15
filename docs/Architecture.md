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

The plugin initially provides a WordPress-native public site structure for a performing-arts organization, while keeping business functions such as performances and ticketing extensible.

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

Members are independent display-oriented records/content and are not authenticated users by default.

A member may contain:

- name (required)
- slug (required internally; generated automatically and editable)
- profile image
- roles
- profile / biography
- SNS links
- organization-defined common custom fields
- display order

### 5.1 Roles

Roles are provided as a built-in multi-select preset rather than free text.

```text
- Actor / 俳優
- Director / 演出
- Writer / 脚本
- Production / 制作
- Sound / 音響
- Lighting / 照明
- Stage Manager / 舞台監督
- Theater Company Representative / 劇団代表
```

A member may select multiple roles.

### 5.2 Organization-wide custom fields

Custom member fields are defined once for the entire WordPress site / theater organization and then shared by all members.

Each field has at least:

- internal field ID
- field name
- description/help text
- active/inactive state
- display order

Each member stores a value against the shared field definition.

The lifecycle rules are:

- **Add:** creates a new shared field. It appears for all members with an initially empty value.
- **Edit:** changes the field definition (for example field name or help text) without changing its internal ID or existing member values.
- **Reorder:** changes the shared display order for every member.
- **Deactivate:** hides the field from member edit screens and public member pages. Existing member values are retained and are not deleted.
- **Reuse:** reactivates a deactivated field. Existing member values become available again and are displayed without re-entry.
- **Delete:** the initial implementation does not provide normal hard deletion of member custom fields. Deactivation is the standard removal operation so that data can be recovered later.

When a field is deactivated, the confirmation message must clearly state that the field will disappear from editing/public display but that entered data will be retained. When a field is reused, the confirmation message must clearly state that the field will return for all members and that previously entered values will be reused.

The field's previous display order should be retained when it is deactivated, so reusing it can restore the prior relative position. It remains possible to reorder it again afterward.

## 6. Homepage and Menu

Content is independent of where it is displayed.

### Homepage

The homepage is a configurable set of sections/slots. Each slot may reference content or headings, and sections can be reordered. Theme settings control presentation.

### Menu

The menu is an independent navigation tree. Items may be folders or links to content/pages, with:

- hierarchy
- label
- enabled/disabled state
- order
- indent/outdent behavior

The same content may be linked from multiple menu locations.

## 7. Authentication and Tickets

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

## 8. Separation of Responsibilities

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

## 9. WordPress Representation

The plugin should use WordPress-native concepts where they fit the content model.

Recommended initial representation:

- site organization information: WordPress/site settings or plugin settings
- contact: system content/page-like content
- production: custom post type with independent `post_name`
- member: dedicated StageArt member content model with individual public detail pages
- notices: WordPress posts or equivalent native publishing content
- performance: StageArt data model related to Production
- ticket / reservation / check-in: dedicated StageArt data models/tables

A separate Organization CPT/table is not required merely to represent the WordPress site's owning theater organization.

## 10. Initial Implementation Order

1. Site-level organization information
2. Contact content
3. Member content and member common-field management
4. Production content and public production pages
5. Homepage configuration
6. Menu configuration
7. Performance management
8. AuthCore integration boundary
9. StageArt account creation/login flow
10. Ticket types
11. Reservations
12. Tickets / check-in

The first public-site milestone is a usable WordPress theater website with organization information, contact content, members, productions, configurable homepage sections, and menu navigation, while maintaining a clean foundation for authenticated ticket management.

# StageArt PlugIn Architecture

## 1. Starting Point

StageArt PlugIn starts from an existing Organization.

```text
Basic Settings
    ↓
Organization
    ↓
Organization Page
    ↓
Production registration
    ↓
Production Page
```

The plugin initially provides a WordPress-native public site structure for a performing-arts organization rather than reproducing the entire StageArt platform.

## 2. Organization

An Organization is the top-level owner of StageArt content on the WordPress site.

The Organization has one public page containing at minimum:

- organization name
- description / introduction
- logo / featured image
- contact / external links
- published productions
- member listing

A site may start with one Organization. The model should not prevent multiple Organizations in the future.

## 3. Production

A Production belongs to exactly one Organization.

Each Production has its own independent public page. The Organization page provides discovery and navigation, while the Production page is the canonical public page for that individual production.

```text
Organization Page
    ├── Production A → /production/a/
    ├── Production B → /production/b/
    └── Production C → /production/c/
```

Production may contain title, description, poster, venue, performance dates, cast, staff, ticket information, external links, and related materials.

## 4. Members

Members are initially display-oriented records, not authenticated StageArt users.

A member entry represents a person who should appear on the Organization or Production page. It may contain:

- display name
- profile image
- biography
- role / position
- SNS or external links
- display order
- visibility

There is no requirement that every member have an AuthCore account.

## 5. Authentication and Tickets

Ticket management is the first area that requires authenticated users.

StageArt PlugIn is an AuthCore **Application**, not an Extension. Its plugin header declares:

```text
AuthCore: application
AuthCore Application Key: stageart
AuthCore Application Name: StageArt
```

StageArt therefore has its own user/account namespace while AuthCore supplies the common authentication engine.

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

AuthCore owns authentication, credentials, sessions and account security. StageArt PlugIn owns ticket types, performances, reservations, tickets, status and check-in business data.

## 6. Separation of Responsibilities

```text
AuthCore
 └── authentication / account / credential / session

StageArt PlugIn
 ├── Organization
 ├── Production
 ├── Performance
 ├── Member Entry
 ├── Ticket Type
 ├── Reservation
 ├── Ticket
 └── Check-in
```

StageArt PlugIn must not duplicate password or credential management. It should use an adapter/service boundary around AuthCore so the domain layer does not directly depend on AuthCore implementation classes.

## 7. WordPress Representation

Recommended initial representation:

- Organization: dedicated StageArt content model or custom post type
- Production: custom post type with an Organization relationship
- Member: dedicated StageArt display-entry model
- Performance: StageArt data model related to Production
- Ticket / Reservation: dedicated StageArt tables

The public URL structure should make the Organization → Production relationship clear while keeping Production pages independently addressable.

## 8. Initial Implementation Order

1. Basic settings
2. Organization creation/editing
3. Organization public page
4. Member display entries
5. Production creation/editing
6. Production public page
7. Performance management
8. AuthCore integration boundary
9. StageArt account creation/login flow
10. Ticket types
11. Reservations
12. Tickets / check-in

The first milestone is a usable organization → production public website with a clean foundation for authenticated ticket management.

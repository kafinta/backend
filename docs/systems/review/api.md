# Review System API Documentation

## Overview
The Review API allows verified buyers to leave, update, and manage reviews on products. It supports aspect ratings, text/images, replies, flagging, helpful votes, and moderation.

---

## Endpoints

### 1. List Reviews for a Product
- **GET /products/{product}/reviews**
- **Purpose:** List all reviews for a product (paginated)
- **Response:** Array of reviews (see ReviewResource)

### 2. Create Review
- **POST /products/{product}/reviews**
- **Auth:** User (must have purchased & received)
- **Fields:**
  - value_for_money (1-5, required)
  - true_to_description (1-5, required)
  - product_quality (1-5, required)
  - shipping (1-5, required)
  - comment (string, optional)
  - images (array of strings, optional)
- **Response:** ReviewResource
- **Errors:** 403 (not eligible), 409 (already reviewed)

### 3. Update Review
- **PATCH /reviews/{id}**
- **Auth:** Review author
- **Fields:** Any of the above (all optional)
- **Response:** ReviewResource

### 4. Delete Review
- **DELETE /reviews/{id}**
- **Auth:** Review author or admin
- **Response:** Success message

### 5. Reply to Review
- **POST /reviews/{id}/reply**
- **Auth:** Any user
- **Fields:** comment (required)
- **Response:** ReviewResource (reply)

### 6. Flag Review
- **POST /reviews/{id}/flag**
- **Auth:** Any user
- **Fields:** reason (required)
- **Response:** Success message

### 7. Helpful Vote
- **POST /reviews/{id}/helpful**
- **Auth:** Any user
- **Fields:** vote ("up" or "down", required)
- **Response:** Success message, updated helpful_votes
- **Notes:** Users can change their vote; only one vote per user per review

---

## Response Structure (ReviewResource)
- id, user, aspect ratings, comment, images, status, created_at
- overall_rating (average of aspects)
- verified_purchase (always true)
- helpful_votes (net upvotes - downvotes)
- replies (recursive)
- flag info

---

## Permissions & Notes
- Only verified buyers can create reviews
- Only review authors or admins can delete
- All users can reply, flag, or vote helpful
- One review per user per product
- Replies do not have aspect ratings 
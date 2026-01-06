# Test2Addon - Form Service for ExpressionEngine

## Overview
The Test2Addon is an ExpressionEngine module designed to simplify form creation, handling, and validation. It includes robust error handling, dynamic attributes, and spam protection mechanisms, making it an excellent choice for developers looking to enhance user input management in their ExpressionEngine projects.

---

## Features
- **Dynamic Form Creation**: Generate customizable forms directly in your templates.
- **Comprehensive Validation**: Apply rules like `required`, `email`, `minLength`, and more to form fields.
- **Error Display**: Show detailed error messages to users when validation fails.
- **Honeypot Spam Protection**: Protect forms from bots with invisible honeypot fields.
- **Secure Hidden Fields**: Safely pass data using encrypted hidden fields.

---

## Installation
1. Copy the `Test2Addon` folder to your `system/user/addons` directory.
2. Log in to your ExpressionEngine Control Panel.
3. Navigate to **Developer** → **Add-Ons**.
4. Install the Test2Addon module.

---

## Template Usage
Use the `{exp:dunique:form}` tag to integrate forms into your templates.

### Example
```
{exp:dunique:form rules:name="required|minLength[4]|noHtml|xss" return_url="/success" error_url="/failure"}
  {if has_errors}
    <div class="alert alert-danger">
      {errors}
        <strong>{errors:field}</strong>: {errors:message}<br>
      {/errors}
    </div>
  {/if}

  <input type="text" name="name" placeholder="Enter your name">
  <button type="submit">Submit</button>
{/exp:dunique:form}
```

---

## Parameters
The `{exp:dunique:form}` tag supports the following parameters:

| Parameter             | Description                                        | Example                              |
|-----------------------|----------------------------------------------------|--------------------------------------|
| `rules:[field_name]`  | Validation rules for the field.                    | `rules:name="required|noHtml"`       |
| `hidden:[field_name]` | Add hidden fields to the form.                     | `hidden:token="abc123"`              |
| `secure:[field_name]` | Securely passes the form ID in hidden fields.      | `secure:token="abc123"`              |
| `return_url`          | URL to redirect to after a successful submission.  | `/thank-you`                         |
| `error_url`           | URL to redirect to if validation fails.            | `/form-error`                        |

---

## Error Handling
- Use `{if has_errors}` to check if the form contains validation errors.
- The `{errors}` tag loops through each error, providing details for display.

| Field             | Description                       |
|-------------------|-----------------------------------|
| `errors:field`    | returns the error field or type   |
| `errors:message`  | returns the error message         |

### Error Example
```
{if has_errors}
  <div class="alert alert-danger">
    {errors}
      <strong>{errors:field}</strong>: {errors:message}<br>
    {/errors}
  </div>
{/if}
```

---

## Security Features
- **Honeypot Fields**: Automatically includes `_malicious_name` and `_malicious_email` fields to detect bots.
- **Encryption**: Hidden fields and sensitive data are encrypted using ExpressionEngine's encryption library.

---

## Methods
The module provides the following methods for advanced customization:

### `ee('dunique')->make($tagdata, $params, $action)`
- **Purpose**: Generates a form with dynamic attributes.
- **Parameters**:
  - `$tagdata`: The template tag content.
  - `$params`: Parameters defined in the tag.
  - `$action`: The action URL or method for the form.
- **Returns**: An array containing form details.

### `ee('dunique')->sanitize($_POST)`
- **Purpose**: Cleans and validates form data from malicious input.
- **Parameters**:
  - `$post`: POST data to sanitize.
- **Returns**: Sanitized data array.

### `ee('dunique')->id(Request $request)`
- **Purpose**: Retrieves the form ID from the request.
- **Parameters**:
  - `$request`: The request object containing form data.
- **Returns**: A string ID.

### `ee('dunique')->handleErrors(Request $request, string $id, array $errors)`
- **Purpose**: Handles form errors and sets flash data.
- **Parameters**:
  - `$request`: The request object.
  - `$id`: The form ID.
  - `$errors`: Array of validation errors.
- **Returns**: Redirects or an error response.

### `ee('dunique')->setSuccessful(string $id)`
- **Purpose**: Marks the form submission as successful.
- **Parameters**:
  - `$id`: The form ID.

---

## Example

```
{exp:dunique:form rules:name="required|minLength[4]|noHtml|xss"}
  {if has_errors}
  <div class="alert alert-danger">
  {errors}
    <strong>{errors:field}</strong> - {errors:message}<br>
  {/errors}
  </div>
  {/if}
  {if is_send}
  <div class="alert alert-success">
    Form send successfully
  </div>
  {/if}
  <input type="text" name="name" id="">
  <button type="submit">Submit</button>
{/exp:dunique:form}
```
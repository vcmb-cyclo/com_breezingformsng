# Your first form: a working contact form

This guide takes you from a fresh BreezingForms NG installation to a contact form that visitors can use. It shows two ways. The quick way installs a ready-made sample form. The other way builds a "Contact us" form yourself in QuickMode, the form editor. You then set up the e-mails, show the form on your site, test it and run through a short checklist before going live.

## Before you start

- BreezingForms NG is installed. If not, see [Installing and updating BreezingForms NG](01-installation.md).
- You are logged in to the Joomla administrator as a Super User. Installing a sample form needs the Configure permission ("administrator rights").
- Joomla can send e-mail. See step 1 of the checklist at the end.
- You know which e-mail address should receive the messages.

All screens are in **Components > BreezingForms NG**. The side menu has these entries: Forms, Records, Scripts (JS), Pieces (PHP), Integrator, Tools, About.

## The quick way: install the sample contact form

1. Go to **Components > BreezingForms NG > Tools**.
2. Find the card **Sample data**. The text says the sample forms are ready to start from and are added to the site menu under "Tests".
3. Under **Business templates**, tick **Professional contact form**. It has required fields, hints, a consent check box, a security code, an e-mail to the site, a copy to the sender and a thank-you page.
4. Click **Install**. Confirm the question.
5. You see: "Sample form "Professional contact form" installed. It is in the site menu, under "Tests"."
6. The card now shows the badge **Installed** and a **Manage** link. **Manage** opens the form in QuickMode so you can change it.
7. Open your website and look in the menu for the **Tests** entry. The form is called **(BF) Sample contact form**. You can use it to test everything in this guide.

The sample is never overwritten. If it is already installed, you see "already installed: it was left as it is".

The form's internal name is `Sample_Contact`. It sends the notification to the default address of the component (see "Set the notification e-mail" below). Set that address before you test.

To build the form yourself, continue with the next section.

## Create the form from scratch

### 1. Create the empty form

1. Go to **Components > BreezingForms NG > Forms**.
2. Click **New** in the toolbar.
3. On the **General** tab fill in:
   - **Title**: `Contact us`. The title is required.
   - **Name**: `Contact`. Use letters and digits only, with no spaces. The name must be unique. It is the name you will use to show the form on the site. If the name is not valid you see "Please enter a valid identifier for name."
   - **Pages**: leave `1` for now. You add the thank-you page later.
   - **Published**: leave **Yes**.
   - **Log submissions to database**: leave **Yes**. This stores every submission so that you can read it under Records.
4. Click **Save** in the toolbar. BreezingForms NG opens the form in QuickMode.

### 2. Add the page, the section and the first element

The QuickMode editor has a tree on the left and three tabs on the right: **Properties**, **Advanced** and **Options**. The tree starts with the form at the top. A form contains pages. A page contains sections. A section contains elements.

1. In the tree, select the form at the top. Click **Page**. A page is added.
2. With the page selected, click **Section**. A section is added.
3. With the section selected, click **Element**. A new element of type **Textfield** is added.

If you see "The form can only contain pages", "A page can only be added to the form" or "An element cannot contain anything", you have selected the wrong item in the tree. Select the item the message asks for and try again.

### 3. Set the three elements

Click an element in the tree to edit it. On the **Properties** tab you see **Type**, **Label** and **Name**. The label is what the visitor sees. The name must be unique in the form, with letters and digits only.

Make these three elements.

**Element 1: your name**

| Where | Setting | Value |
| --- | --- | --- |
| Properties | **Type** | Textfield |
| Properties | **Label** | Your name |
| Properties | **Name** | `YourName` |
| Properties | **Placeholder** | Jane Smith |
| Properties, Validation | **Required** | tick (shows an asterisk) |
| Properties, Validation | **Validation**, **Type** | **Library** |
| Properties, Validation | **Script** | Value not empty |
| Properties, Validation | **Error Message** | Please enter your name. |

**Element 2: your e-mail address.** Click **Element** again (with the section selected) to add it.

| Where | Setting | Value |
| --- | --- | --- |
| Properties | **Type** | Textfield |
| Properties | **Label** | Your e-mail address |
| Properties | **Name** | `Email` |
| Properties | **Placeholder** | name@example.com |
| Properties, Validation | **Required** | tick |
| Properties, Validation | **Validation**, **Type** | **Library** |
| Properties, Validation | **Script** | Valid email |
| Properties, Validation | **Error Message** | Please enter a valid e-mail address. |
| Advanced | **Mailback?** | tick |

**Element 3: the message.** Click **Element**, then change **Type** to **Textarea**.

| Where | Setting | Value |
| --- | --- | --- |
| Properties | **Type** | Textarea |
| Properties | **Label** | Your message |
| Properties | **Name** | `Message` |
| Properties, Validation | **Required** | tick |
| Properties, Validation | **Validation**, **Type** | **Library** |
| Properties, Validation | **Script** | Value not empty |
| Properties, Validation | **Error Message** | Please write a message. |

Important points:

- **Required** only shows the red asterisk next to the label. The editor says it does not check the field. The check is done by the **Validation** setting. Always set both.
- **Mailback?** on the Email element tells BreezingForms NG that this field holds the visitor's e-mail address. The confirmation e-mail is sent to it.
- **Logging** (on the **Advanced** tab) is on by default for new elements. Keep it on for these three, so their values are saved in the record and shown in the e-mails.
- If you change the **Type** of an element that already has settings, those settings are lost.

### 4. Add a captcha (recommended)

1. Select the section and click **Element**.
2. Change **Type** to **Captcha** (in the group **Security and payment**). Set **Label** to `Security code` and **Name** to `Captcha`.
3. Set **Logging** to off on the **Advanced** tab. A captcha code does not need to be stored.

The group **Security and payment** also has **ReCaptcha**. Use it only if you have configured it.

### 5. Set the submit button and the thank-you page

Select the form at the top of the tree. The **Properties** tab shows **Form properties**.

- **Include submit button**: **Yes** (the default). You do not need a Submit Button element.
- **Submit button label**: `Send`. The default is "Submit".

For a thank-you page:

1. Tick **Last page is thank you page**. The editor explains: the last form page is used as the thank-you page. Without this option, the form returns to page 1 after the visitor submits.
2. Select the form at the top and click **Page** to add page 2. Page 1 keeps your fields, page 2 becomes the thank-you page.
3. Select page 2. On the **Page Properties** tab, find **Intro** and click **Edit**. Type your message, for example: `Thank you. We have received your message and will answer within two working days.`

Do not add fields to page 2. Tick **Last page is thank you page** before you add page 2: the editor then sets up the switch to the last page after a successful submit.

### 6. Set the notification e-mail

Select the form at the top of the tree. In **Form properties**:

1. Tick **Mail notification**.
2. In **Mail recipient(s)**, type the address that should receive the messages, for example `info@example.com`. Separate several addresses with a semicolon.

What happens:

- If you fill in **Mail recipient(s)**, the notification goes to those addresses.
- If you leave it empty, it goes to the address in the component options, **Default email notification address** (**Components > BreezingForms NG > Forms > Options**). That field contains "Enter your email address here" until you change it. Change it or fill in **Mail recipient(s)**.
- The notification is sent from the sender address and name of the Joomla Global Configuration, unless you set **Alt. Mail From** and **Alt. From Name** in the Options tab (below).

### 7. Set the confirmation e-mail to the visitor

1. Open the **Options** tab of the editor. It shows the same options as the form edit screen. If you see "Save the form first to access these options", click **Save** and come back.
2. Open **Email Notifications**, then the sub-tab **User Confirmation**.
3. **User Email Notification**: **Yes**. This is the default. **None** turns the confirmation off.
4. **User Mail Subject**: `We received your message`.
5. **Alt. From Name (User)** and **Alt. Mail From (User)**: optional. Leave empty to use the Joomla sender.
6. **Report**: **Nonempty values** (default) lists the values the visitor filled in.

The confirmation is sent to the address entered in the element that has **Mailback?** ticked (your Email element). Without that element, no confirmation is sent.

On the sub-tab **Administrator Notification** you can also set **Custom Mail Subject** (for example `New message from the website`). The setting **Admin Email Notification** has these choices: **None**, **Yes** (use the default address of the component) and **Log Only** (use the addresses in **Admin Email Address(es)**, one or more separated by semicolons). The QuickMode fields **Mail notification** and **Mail recipient(s)** set these for you. When you fill in a recipient, the form uses the second choice, which the screen labels **Log Only**.

### 8. Store submissions in the database

On the **Options** tab, **General** tab, **Log submissions to database** must be **Yes**. The tip says: "Saves each submission as a record in the database." It is **Yes** by default.

### 9. Save

Click **Save** in the toolbar of the editor. While it saves you see "Saving… (1/2)" and so on. A badge **Unsaved changes** shows when there is something to save. If you see "The form has changed or another user has locked it. Reload the editor before trying to save again.", reload the page and make your changes again.

The toolbar also has **Preview**, **Site Preview** and **Close**. **Preview** opens the form in a window inside the administrator. **Site Preview** opens the form as the site shows it.

## Publish the form and show it on your website

A form is shown only when it is published. In the editor, the **Published** switch at the top right (next to **Debug**) shows the state. In the form list the column **Published** has the same switch. Click it to publish or unpublish.

There are three ways to show the form.

### Option A: a menu item (a page of its own)

1. Go to **Menus** and open the menu you want, then click **New**.
2. Enter a **Title**, for example `Contact us`.
3. Click **Select** next to **Menu Item Type**. Open **BreezingForms NG** and choose the item **Add Form**.
4. In the **Form name** field, type the **Name** of your form: `Contact`. It is the name, not the title.
5. Leave **Page number** at `1`.
6. Leave the other settings as they are. They are **Submission editable by user?**, **Remove existing submissions?**, **Run in iframe**, **Border** and more.
7. Click **Save & Close**.
8. If the menu is not shown on your site yet, add the menu to a module position, or use the menu item link directly.

This is the same kind of menu item the sample form creates.

### Option B: inside an article (content plugin)

The plugin **BreezingForms NG - Content** is installed and enabled with the component. Type this on its own line in an article:

```
{BreezingForms:Contact}
```

The name is case-sensitive and must be a published form. The full syntax is `{BreezingForms:formname,page,border,urlparams,suffix,editable,editable_override}`. The default page is 1. If the form is missing or unpublished, the page shows "Form Contact was not found or is unpublished." In the plugin settings, **Use an iframe** shows the form in an iframe. Keep the tag on one line in the editor.

### Option C: in a module position

1. Go to **Content > Site Modules** (**System > Site Modules**) and click **New**.
2. Choose the module type **BreezingForms NG**.
3. Set **Form name** to `Contact`. It is required. Leave **Start page** at `1`.
4. Choose a **Position** and set **Status** to published. Under **Menu Assignment**, choose the pages where the form shows.
5. Click **Save & Close**.

### Direct link

You can open a form by its number:

```
https://example.com/index.php?option=com_breezingformsng&ff_form=ID&ff_page=1
```

Replace `ID` with the number of the form in the form list (column **ID**). You can also use `&ff_name=Contact` with the form name. This is the address the editor uses for **Preview** and **Site Preview**.

## Check that it worked

1. Open the form on your site, as a visitor (log out or use a private window).
2. First send it empty. You should see your error messages and the red asterisks.
3. Fill in the name, a real e-mail address you can read, and a message. Fill in the security code. Click **Send**.
4. You should see your thank-you page. If you did not use a thank-you page, the form shows page 1 again.
5. Check the inbox of the notification address. The message shows the values the visitor entered.
6. Check the inbox of the visitor address. It holds the confirmation.
7. Go to **Components > BreezingForms NG > Records**. The new submission is the first line. The columns are **ID**, **Form**, **IP**, **User**, **Status**, **Submitted** and **Modified**. Click a line to open it. The page shows **Submitted input values** with the **Element ID**, **Type** and **Value** of each field, and **Submitter information** (IP, browser and system).

## Troubleshooting

- **The form does not show on the site.** The form must be **Published**. For a menu item, check that **Form name** is the form **Name** and not the **Title**. For the plugin, check the case of the name.
- **No e-mail arrives.** Check the Joomla Global Configuration (see the checklist) and send a test mail from there. Check your spam folder. Check that **Mail notification** is ticked and that the recipient address (or the **Default email notification address**) is correct. The default value is "Enter your email address here", which is not an address.
- **The visitor gets no confirmation.** Check that **User Email Notification** is **Yes**, and that the e-mail element has **Mailback?** ticked.
- **The form can be sent with an empty field.** **Required** only shows the asterisk. Set **Validation** to **Library** and choose **Value not empty** (or **Valid email**).
- **The record is empty or a value is missing.** The element needs **Logging** on (Advanced tab).
- **No record appears.** **Log submissions to database** is set to **No**.
- **"You cannot create a page here!" or "You cannot create this here!"** Select the right item in the tree first (see step 2).
- **Another user has the form open.** Forms are locked while someone edits them. Click **Close** in the editor, or ask the other user to.

## Checklist before you go live

1. **Joomla sends e-mail.** In **System > Global Configuration > Server**, check the Mail settings: mailer, **From Email** and **From Name**. Use an address of your own domain as sender. Test with the Send Test Mail button.
2. **Notification address.** Set **Mail recipient(s)** in the form, or the **Default email notification address** in the component options.
3. **Spam.** Keep a **Captcha** (or ReCaptcha) element in public forms. Keep the form short. Open the form in a private window and send a few tests.
4. **Permissions.** Open **Components > BreezingForms NG > Forms > Options > Permissions**. Only trusted groups should be allowed to create and edit forms and to see records. Records hold personal data.
5. **Visibility.** The form is **Published**. The menu item, article or module is published and its **Access** level fits your visitors.
6. **Texts.** Read the error messages, the thank-you page and both e-mails. If your site has several languages, translate them: the editor shows a language switch when more than one language is installed.
7. **Consent.** If you collect personal data, tell visitors what you do with it. The sample contact form has a consent check box you can copy.
8. **Clean up.** Unpublish or remove the sample forms and the **Tests** menu entries.

## See also

- [Installing and updating BreezingForms NG](01-installation.md)
- [The QuickMode editor](03-quickmode-editor.md)
- [Field types](04-field-types.md)

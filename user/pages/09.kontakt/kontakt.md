---
title: 'Kontakt'
intro: 'Fragen zum Anlass? Wir melden uns.'
text: ""
formularaktiv: true
dankettext: 'Danke für deine Nachricht — wir melden uns.'
kopfhintergrund:
  - kopfbild.jpg
# aus Kirby übernommen: kr534eob3ejrrbqr
# Gravs Formular-Plugin übernimmt Prüfung und Versand.
# Kein Google-reCAPTCHA: eine Spamfalle genügt für eine Vereinsseite
# und schickt keine Besucherdaten zu Dritten.
form:
  name: kontakt
  fields:
    - name: name
      label: Name
      type: text
      validate:
        required: true
    - name: email
      label: E-Mail
      type: email
      validate:
        required: true
    - name: betreff
      label: Betreff
      type: text
      validate:
        required: true
    - name: nachricht
      label: Nachricht
      type: textarea
      validate:
        required: true
        min: 10
    - name: website
      type: honeypot

  buttons:
    - type: submit
      value: 'Nachricht senden'

  process:
    - email:
        from: '{{ config.site.verein.formular_empfaenger }}'
        to: '{{ config.site.verein.formular_empfaenger }}'
        reply_to: '{{ form.value.email }}'
        subject: 'Kontaktformular: {{ form.value.betreff }}'
        body: |
          Name:    {{ form.value.name }}
          E-Mail:  {{ form.value.email }}
          Betreff: {{ form.value.betreff }}

          {{ form.value.nachricht }}
    - message: 'Danke für deine Nachricht — wir melden uns.'
    - reset: true

---


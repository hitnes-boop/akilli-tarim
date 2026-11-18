# Demo kullanıcılara gerçek parola özeti yazar (parola: 1234).
# PHP tarafı ile aynı algoritma: PBKDF2-HMAC-SHA256, 100000 tur, hex.
import sqlite3, os, secrets, hashlib
HERE = os.path.dirname(os.path.abspath(__file__))
TUR = 100000
def ozet(parola, tuz): return hashlib.pbkdf2_hmac('sha256', parola.encode(), tuz.encode(), TUR).hex()
d = sqlite3.connect(os.path.join(HERE, 'akilli_tarim_v1.db'))
for kid, email in d.execute('SELECT kullanici_id, email FROM KULLANICI').fetchall():
    tuz = secrets.token_hex(16)
    d.execute('UPDATE KULLANICI SET parola_tuz=?, parola_hash=? WHERE kullanici_id=?', (tuz, ozet('1234', tuz), kid))
d.commit()
for r in d.execute('SELECT kullanici_id, ad, email, rol FROM KULLANICI'): print(r)

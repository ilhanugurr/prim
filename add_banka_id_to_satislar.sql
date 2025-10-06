-- Satışlar tablosuna banka_id kolonu ekleme
ALTER TABLE satislar ADD COLUMN IF NOT EXISTS banka_id INT AFTER durum;
ALTER TABLE satislar ADD CONSTRAINT fk_satislar_banka FOREIGN KEY (banka_id) REFERENCES bankalar(id) ON DELETE SET NULL;


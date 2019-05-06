SELECT * FROM estabelecimento

SELECT datalog, dtemissao,* FROM lancamento ORDER BY codlancto DESC LIMIT 5
SELECT codlanctogru, favorecido, valorbruto, dtemissao, codbanco, pagrec, referencia FROM lancamentogru WHERE usuario='CSV'
SELECT codlanctogru, favorecido, valorparcela, dtemissao, dtvencto, codbanco, pagrec, referencia FROM lancamento WHERE usuario='CSV'
DELETE FROM lancamentogru WHERE usuario='CSV'

CREATE OR REPLACE FUNCTION stpr_lancamentogru_beforesave()
  RETURNS trigger AS
$BODY$
BEGIN
	-- Se valorliquido for igual a zero não gravar
	IF new.valorliquido <= 0 OR new.valorbruto <= 0 THEN
		RAISE EXCEPTION ' O valor liquido e o valor bruto precisam ser maior que zero nos lancamentos.';
	END IF;

	-- Preenche o favorecido
	IF new.favorecido IS NULL OR char_length(trim(new.favorecido)) = 0 THEN
		IF new.tipoparceiro = 'A' THEN
			new.favorecido := (SELECT nome FROM administradora WHERE codadminist = new.codparceiro);
		ELSEIF new.tipoparceiro = 'C' THEN
			new.favorecido := (SELECT nome FROM cliente WHERE codcliente = new.codparceiro);
		ELSEIF new.tipoparceiro = 'E' THEN
			new.favorecido := (SELECT nome FROM estabelecimento WHERE codestabelec = new.codparceiro);
		ELSEIF new.tipoparceiro = 'F' THEN
			new.favorecido := (SELECT nome FROM fornecedor WHERE codfornec = new.codparceiro);
		ELSEIF new.tipoparceiro = 'T' THEN
			new.favorecido := (SELECT nome FROM transportadora WHERE codtransp = new.codparceiro);
		ELSEIF new.tipoparceiro = 'U' THEN
			new.favorecido := (SELECT nome FROM funcionario WHERE codfunc = new.codparceiro);
		END IF;
	END IF;

	-- Preenche a Categoria do Lancamento
	IF new.codcatlancto IS NULL AND  new.codsubcatlancto IS NULL THEN
			new.codcatlancto := (SELECT codcatlancto FROM fornecedor WHERE codfornec = new.codparceiro);
			new.codsubcatlancto := (SELECT codsubcatlancto FROM fornecedor WHERE codfornec = new.codparceiro);
	END IF;

	-- Preenche Codlanctogru
	IF new.codlanctogru IS NULL THEN
			new.codlanctogru := (SELECT MAX(codlanctogru)+1 FROM lancamentogru);
	END IF;

	RETURN new;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION stpr_lancamentogru_beforesave()
  OWNER TO postgres;
--SELECT MAX(codlanctogru)+1 FROM lancamentogru 

INSERT INTO lancamentogru (codlanctogru, codestabelec, pagrec, tipoparceiro, codparceiro, codcondpagto, codbanco, codespecie, valorbruto, dtlancto, dtemissao, codcatlancto, codsubcatlancto, codmoeda, favorecido, referencia, valorliquido, usuario, datalog) VALUES (NULL, 1, 'P', 'F', 45, 2, 3, 8, 1500.00, '6/1/2016', '6/1/2016', NULL, NULL, 1, 'Banco - Deposito', 'Presente Casamento Renato e Meg', 1500.00, 'CSV', CURRENT_DATE);
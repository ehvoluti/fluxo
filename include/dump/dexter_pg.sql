--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: clientes; Type: TABLE; Schema: public; Owner: dexter; Tablespace: 
--

CREATE TABLE clientes (
    id integer NOT NULL,
    nome_razao character varying NOT NULL,
    cpf_cnpj character varying NOT NULL,
    email character varying NOT NULL,
    senha character varying NOT NULL,
    telefone character varying NOT NULL,
    celular character varying,
    cep character varying(8) NOT NULL,
    endereco character varying NOT NULL,
    bairro character varying NOT NULL,
    cidade character varying NOT NULL,
    estado character varying NOT NULL
);


ALTER TABLE public.clientes OWNER TO dexter;

--
-- Name: clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: dexter
--

CREATE SEQUENCE clientes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.clientes_id_seq OWNER TO dexter;

--
-- Name: clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dexter
--

ALTER SEQUENCE clientes_id_seq OWNED BY clientes.id;


--
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: dexter
--

SELECT pg_catalog.setval('clientes_id_seq', 2, true);


--
-- Name: encomendas; Type: TABLE; Schema: public; Owner: dexter; Tablespace: 
--

CREATE TABLE encomendas (
    id integer NOT NULL,
    fun_id integer NOT NULL,
    cli_id integer NOT NULL,
    l_pac integer NOT NULL,
    a_pac integer NOT NULL,
    p_pac integer NOT NULL,
    ori_cep character varying(8) DEFAULT NULL::character varying,
    ori_endereco character varying(255) NOT NULL,
    ori_bairro character varying(100) NOT NULL,
    ori_cidade character varying(100) NOT NULL,
    ori_estado character varying(2) NOT NULL,
    dst_nome character varying(255) NOT NULL,
    dst_cep character varying(8) NOT NULL,
    dst_endereco character varying(255) NOT NULL,
    dst_bairro character varying(100) NOT NULL,
    dst_cidade character varying(100) NOT NULL,
    dst_estado character varying(2) NOT NULL,
    distancia double precision NOT NULL,
    tip_id integer NOT NULL,
    mot_id integer,
    data_coleta date,
    data_prevista date,
    data_entrega date,
    transito integer DEFAULT 0,
    entregue integer DEFAULT 0,
    coleta integer DEFAULT 0,
    seguro integer DEFAULT 0
);


ALTER TABLE public.encomendas OWNER TO dexter;

--
-- Name: encomendas_id_seq; Type: SEQUENCE; Schema: public; Owner: dexter
--

CREATE SEQUENCE encomendas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.encomendas_id_seq OWNER TO dexter;

--
-- Name: encomendas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dexter
--

ALTER SEQUENCE encomendas_id_seq OWNED BY encomendas.id;


--
-- Name: encomendas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: dexter
--

SELECT pg_catalog.setval('encomendas_id_seq', 14, true);


--
-- Name: fale_conosco; Type: TABLE; Schema: public; Owner: dexter; Tablespace: 
--

CREATE TABLE fale_conosco (
    id integer NOT NULL,
    nome character varying NOT NULL,
    assunto character varying NOT NULL,
    mensagem text NOT NULL
);


ALTER TABLE public.fale_conosco OWNER TO dexter;

--
-- Name: fale_conosco_id_seq; Type: SEQUENCE; Schema: public; Owner: dexter
--

CREATE SEQUENCE fale_conosco_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.fale_conosco_id_seq OWNER TO dexter;

--
-- Name: fale_conosco_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dexter
--

ALTER SEQUENCE fale_conosco_id_seq OWNED BY fale_conosco.id;


--
-- Name: fale_conosco_id_seq; Type: SEQUENCE SET; Schema: public; Owner: dexter
--

SELECT pg_catalog.setval('fale_conosco_id_seq', 1, false);


--
-- Name: funcionarios; Type: TABLE; Schema: public; Owner: dexter; Tablespace: 
--

CREATE TABLE funcionarios (
    id integer NOT NULL,
    prf_id integer NOT NULL,
    nome character varying NOT NULL,
    email character varying NOT NULL,
    senha character varying NOT NULL
);


ALTER TABLE public.funcionarios OWNER TO dexter;

--
-- Name: funcionarios_id_seq; Type: SEQUENCE; Schema: public; Owner: dexter
--

CREATE SEQUENCE funcionarios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.funcionarios_id_seq OWNER TO dexter;

--
-- Name: funcionarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dexter
--

ALTER SEQUENCE funcionarios_id_seq OWNED BY funcionarios.id;


--
-- Name: funcionarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: dexter
--

SELECT pg_catalog.setval('funcionarios_id_seq', 3, true);


--
-- Name: perfis; Type: TABLE; Schema: public; Owner: dexter; Tablespace: 
--

CREATE TABLE perfis (
    id integer NOT NULL,
    nome character varying(255) NOT NULL
);


ALTER TABLE public.perfis OWNER TO dexter;

--
-- Name: perfis_id_seq; Type: SEQUENCE; Schema: public; Owner: dexter
--

CREATE SEQUENCE perfis_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.perfis_id_seq OWNER TO dexter;

--
-- Name: perfis_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dexter
--

ALTER SEQUENCE perfis_id_seq OWNED BY perfis.id;


--
-- Name: perfis_id_seq; Type: SEQUENCE SET; Schema: public; Owner: dexter
--

SELECT pg_catalog.setval('perfis_id_seq', 2, true);


--
-- Name: tipos_encomenda; Type: TABLE; Schema: public; Owner: dexter; Tablespace: 
--

CREATE TABLE tipos_encomenda (
    id integer NOT NULL,
    nome character varying NOT NULL,
    valor_km double precision NOT NULL,
    prazo_maximo integer NOT NULL
);


ALTER TABLE public.tipos_encomenda OWNER TO dexter;

--
-- Name: tipos_encomenda_id_seq; Type: SEQUENCE; Schema: public; Owner: dexter
--

CREATE SEQUENCE tipos_encomenda_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.tipos_encomenda_id_seq OWNER TO dexter;

--
-- Name: tipos_encomenda_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: dexter
--

ALTER SEQUENCE tipos_encomenda_id_seq OWNED BY tipos_encomenda.id;


--
-- Name: tipos_encomenda_id_seq; Type: SEQUENCE SET; Schema: public; Owner: dexter
--

SELECT pg_catalog.setval('tipos_encomenda_id_seq', 2, true);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY clientes ALTER COLUMN id SET DEFAULT nextval('clientes_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY encomendas ALTER COLUMN id SET DEFAULT nextval('encomendas_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY fale_conosco ALTER COLUMN id SET DEFAULT nextval('fale_conosco_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY funcionarios ALTER COLUMN id SET DEFAULT nextval('funcionarios_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY perfis ALTER COLUMN id SET DEFAULT nextval('perfis_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY tipos_encomenda ALTER COLUMN id SET DEFAULT nextval('tipos_encomenda_id_seq'::regclass);


--
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: dexter
--

COPY clientes (id, nome_razao, cpf_cnpj, email, senha, telefone, celular, cep, endereco, bairro, cidade, estado) FROM stdin;
1	DHL Logi­stica	09830583205	contato@dhl.com	123456	4350984508	94894859	89899999	Rua dos Transportes, 99	Jardim Caminhão	São Paulo	SP
2	Maddog Co.	24325236532	company@maddog.com	123456	23526	345346346	34634643	ji jijoij iihoiho mmpo, 77	okewgopkeowpg	uhohouh	FN
\.


--
-- Data for Name: encomendas; Type: TABLE DATA; Schema: public; Owner: dexter
--

COPY encomendas (id, fun_id, cli_id, l_pac, a_pac, p_pac, ori_cep, ori_endereco, ori_bairro, ori_cidade, ori_estado, dst_nome, dst_cep, dst_endereco, dst_bairro, dst_cidade, dst_estado, distancia, tip_id, mot_id, data_coleta, data_prevista, data_entrega, transito, entregue, coleta, seguro) FROM stdin;
8	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
9	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
10	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
11	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
12	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
13	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
14	1	1	102	205	50	05331020	Rua Parnamirim, 110	Jaguarão	São Paulo	SP	Kleber Calegario Batista	05330011	Rua Alarico Franco Caiubi, 549	Jaguarão	São Paulo	SP	100	1	3	2011-10-18	\N	\N	0	0	0	0
\.


--
-- Data for Name: fale_conosco; Type: TABLE DATA; Schema: public; Owner: dexter
--

COPY fale_conosco (id, nome, assunto, mensagem) FROM stdin;
\.


--
-- Data for Name: funcionarios; Type: TABLE DATA; Schema: public; Owner: dexter
--

COPY funcionarios (id, prf_id, nome, email, senha) FROM stdin;
1	1	Admin	admin@dexter.com	123456
2	1	Fulano	fulano@dexter.com	123456
3	2	João	joao@dexter.com	123456
\.


--
-- Data for Name: perfis; Type: TABLE DATA; Schema: public; Owner: dexter
--

COPY perfis (id, nome) FROM stdin;
1	administrador
2	motorista
\.


--
-- Data for Name: tipos_encomenda; Type: TABLE DATA; Schema: public; Owner: dexter
--

COPY tipos_encomenda (id, nome, valor_km, prazo_maximo) FROM stdin;
1	Expressa	0.97999999999999998	2
2	Normal	0.45000000000000001	5
\.


--
-- Name: clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: dexter; Tablespace: 
--

ALTER TABLE ONLY clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id);


--
-- Name: encomendas_pkey; Type: CONSTRAINT; Schema: public; Owner: dexter; Tablespace: 
--

ALTER TABLE ONLY encomendas
    ADD CONSTRAINT encomendas_pkey PRIMARY KEY (id);


--
-- Name: fale_conosco_pkey; Type: CONSTRAINT; Schema: public; Owner: dexter; Tablespace: 
--

ALTER TABLE ONLY fale_conosco
    ADD CONSTRAINT fale_conosco_pkey PRIMARY KEY (id);


--
-- Name: funcionarios_pkey; Type: CONSTRAINT; Schema: public; Owner: dexter; Tablespace: 
--

ALTER TABLE ONLY funcionarios
    ADD CONSTRAINT funcionarios_pkey PRIMARY KEY (id);


--
-- Name: perfis_pkey; Type: CONSTRAINT; Schema: public; Owner: dexter; Tablespace: 
--

ALTER TABLE ONLY perfis
    ADD CONSTRAINT perfis_pkey PRIMARY KEY (id);


--
-- Name: tipos_encomenda_pkey; Type: CONSTRAINT; Schema: public; Owner: dexter; Tablespace: 
--

ALTER TABLE ONLY tipos_encomenda
    ADD CONSTRAINT tipos_encomenda_pkey PRIMARY KEY (id);


--
-- Name: encomendas_cli_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY encomendas
    ADD CONSTRAINT encomendas_cli_id_fkey FOREIGN KEY (cli_id) REFERENCES clientes(id) ON DELETE CASCADE;


--
-- Name: encomendas_fun_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY encomendas
    ADD CONSTRAINT encomendas_fun_id_fkey FOREIGN KEY (fun_id) REFERENCES funcionarios(id) ON DELETE CASCADE;


--
-- Name: encomendas_mot_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY encomendas
    ADD CONSTRAINT encomendas_mot_id_fkey FOREIGN KEY (mot_id) REFERENCES funcionarios(id) ON DELETE CASCADE;


--
-- Name: encomendas_tip_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY encomendas
    ADD CONSTRAINT encomendas_tip_id_fkey FOREIGN KEY (tip_id) REFERENCES tipos_encomenda(id) ON DELETE CASCADE;


--
-- Name: funcionarios_prf_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: dexter
--

ALTER TABLE ONLY funcionarios
    ADD CONSTRAINT funcionarios_prf_id_fkey FOREIGN KEY (prf_id) REFERENCES perfis(id) ON UPDATE CASCADE;


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--


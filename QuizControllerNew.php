<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Form\SelectionType;
use App\Repository\MatiereRepository;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPExcel_IOFactory;

/**
 * @Route("/quiz")
 */
class QuizController extends AbstractController
{
    /**
     * @Route("/index", name="quiz_index", methods={"GET"})
     */
    public function index(QuizRepository $quizRepository): Response
    {
        $mesQuiz = $quizRepository->getQuizByUser($this->getUser());

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'mesQuiz' =>$mesQuiz,
            'nbreDeQuiz' => count($mesQuiz)
        ]);
    }
    /**
     * @Route("/search", name="quiz_search", methods={"GET"})
     */
    public function search(QuizRepository $quizRepository, Request $request): Response
    {
        $search = $request->request->get("reponse");
        $mesQuiz = $quizRepository->getQuizByUserBySearch($this->getUser(), $search);

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'mesQuiz' =>$mesQuiz,
            'nbreDeQuiz' => count($mesQuiz)
        ]);
    }

    /**
     * @Route("/AFaire", name="quiz_a_faire", methods={"GET"})
     */
    public function getQuizByUserAFaire(QuizRepository $quizRepository): Response
    {
        $qcmAFaire = $quizRepository->getQuizByUserAFaire($this->getUser());

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'mesQuiz' =>$qcmAFaire,
            'nbreDeQuiz' => count($qcmAFaire)
        ]);
    }

    /**
     * @Route("/nok", name="quiz_nok", methods={"GET"})
     */
    public function quizNok(QuizRepository $quizRepository): Response
    {
        $mesQuiz = $quizRepository->getQuizByUserNok($this->getUser());

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'mesQuiz' =>$mesQuiz,
            'nbreDeQuiz' => count($mesQuiz)
        ]);
    }

    /**
     * @Route("/ok", name="quiz_ok", methods={"GET"})
     */
    public function quizOk(QuizRepository $quizRepository): Response
    {
        $mesQuiz = $quizRepository->getQuizByUserOk($this->getUser());

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'mesQuiz' =>$mesQuiz,
            'nbreDeQuiz' => count($mesQuiz)
        ]);
    }
    /**
     * @Route("/validation/{id}", name="quiz_validation", requirements={"id":"[0-9-]+"})
     */
    public function validation(Quiz $quiz, QuizRepository $quizRepository): Response
    {
        $titre = $quiz->getNomDeFichier();
        $verif = preg_match("#A refaire#i", "'.$titre.'");
        if($verif == true){
            $manager = $this->getDoctrine()->getManager();
            $manager->remove($quiz);
            $manager->flush();
        }

        $mesQuiz = $quizRepository->getQuizByUser($this->getUser());

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
            'mesQuiz' => $mesQuiz
        ]);
    }
    /**
     * @Route("/matiere", name="quiz_matiere", methods={"GET"})
     */
    public function matiere(MatiereRepository $matiereRepository): Response
    {
        return $this->render('quiz/navMatiere.html.twig', [
            'matieres' => $matiereRepository->findAll()
        ]);
    }
    /**
     * @Route("/{id<\d+>}", name="QuizParMatiere", methods={"GET"})
     */
    public function QuizParMatiere(QuizRepository $quizRepository,$id): Response

    {
        $QuizParMatiere = $quizRepository->getQuizByMatiere($id);

        return $this->render('quiz/quizParMatiere.html.twig', [
            'QuizParMatiere' => $QuizParMatiere
        ]);
    }

    /**
     * @Route("/{id<\d+>}/mesQuiz", name="mesQuizParMatiere", methods={"GET"})
     */
    public function mesQuizParMatiere(QuizRepository $quizRepository,$id): Response

    {
        $user = $this->getUser();
        if($user != null){
            $mesQuizParMatiere = $quizRepository->getQuizByUserByMatiere($user, $id);
        }
        dump($mesQuizParMatiere);

        return $this->render('quiz/mesQuizParMatiere.html.twig', [
            'mesQuizParMatiere' => $mesQuizParMatiere
        ]);
    }

    //Calcul de la moyenne des quiz par matiere, de la moyenne générale, nombre de mots en anglais, nombre de qcm
    /**
     * @Route("/resultat", name="quiz_resultatParMatiere", methods={"GET"})
     */
    public function resultatParMatiere(QuizRepository $quizRepository, MatiereRepository $matiereRepository): Response
    {   $user = $this->getUser();
        $tabMoyenneParMatiere=[];
        $nbreQuizFais = 0;
        $nbreDeMostAnglais = 0;
        $NbreDeQcm =0;
        if($user != null){
            $sommeResultat = 0;
            $matieres = $matiereRepository->findAll();
            foreach($matieres as $matiere){
                $mesQuiz = $quizRepository->getQuizByUserByMatiere($user, $matiere->getId());
                if($mesQuiz != null){
                    foreach($mesQuiz as $quiz){
                        if(null !== $quiz->getResultat()){
                            $sommeResultat  += $quiz->getResultat();
                            $nbreQuizFais = $nbreQuizFais+1;
                        }
                    }
                    //moyenne par matiere
                    $tabMoyenneParMatiere[$matiere->getIntitule()] = $sommeResultat/$nbreQuizFais;
                    //Calcul du nombre de mots et de qcm en anglais
                    if ($matiere->getIntitule() == 'Anglais'){
                        $quizAnglais = $quizRepository->getMotsApprisAnglaisByUser($user,$matiere);
                        foreach($quizAnglais as $quiz ){
                            if($quiz->getResultat() === null){
                                $nbreDeMostAnglais = $nbreDeMostAnglais + count($quiz->getQuizQuestion());
                                $NbreDeQcm++;
                            }
                        };
                    }
                    $sommeResultat = 0;
                }
            }
        }
        //Calcul de la moyenne générale
        $noteglobale=0;
        foreach($tabMoyenneParMatiere as $note) {
            $noteglobale += $note;
            $moyenneGenerale = $noteglobale / count($tabMoyenneParMatiere);
            $user->setMoyenneQuiz($moyenneGenerale);
        }
        $user->setMoyenneQuiz($moyenneGenerale);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->render('quiz/resultat.html.twig', [
            'tabMoyenneParMatiere' => $tabMoyenneParMatiere,
            'moyenneGenerale' => $moyenneGenerale,
            'motsAnglais' => $nbreDeMostAnglais,
            'NbreDeQcm' =>$NbreDeQcm
        ]);
    }
    /**
     * @Route("/selection", name="quiz_selection", methods={"GET","POST"})
     */
    public function quizSelection(Request $request, QuizRepository $quizRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(SelectionType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $dateDebut = $data->getDateDebut();
            $dateFin = $data->getDateFin();
            $QuizSelectiones = $quizRepository->selectionQuizParDate($dateDebut, $dateFin);

            return $this->render('quiz/index.html.twig', [
                'quizzes' => $quizRepository->findAll(),
                'mesQuiz' => $QuizSelectiones
            ]);
        }

        return $this->render('quiz/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="quiz_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $quiz->getFile();

            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            $objPHPExcel = $objReader->load($file);
            $nbreDeQuestion = 1;
            $fin = $objPHPExcel->getActiveSheet()->getCell('A5')->getValue();
            $i = 5;
            while($fin !=''){
                $fin = $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getValue();
                $i = $i +1;
            }

            for($j=5; $j<$i-1; $j++){
                $quizQuestion[]=str_replace(' ','_',$objPHPExcel->getActiveSheet()->getCell('A'.$j)->getValue());
                $quizReponse[]=str_replace(' ','_',$objPHPExcel->getActiveSheet()->getCell('B'.$j)->getValue());
            }
            $quiz->setEnonce(str_replace(' ','_',$objPHPExcel->getActiveSheet()->getCell('A4')->getValue()));
            $quiz->setQuizQuestion($quizQuestion);
            $quiz->setQuizReponse($quizReponse);
            $user = $this->getUser();
            $quiz->setUser($user);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($quiz);
            $entityManager->flush();

            return $this->redirectToRoute('quiz_index');
        }

        return $this->render('quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @Route("/startQuiz/{id}", name="quiz_start_quiz")
     */
    public function startQuiz(Quiz $quiz): Response
    {
        //dump($quiz); die();

        return $this->render('quiz/startQuiz.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    /**
     * @Route("/correction/{id}", name="quiz_correction", requirements={"id":"[0-9-]+"})
     */
    public function correction(Quiz $quiz, Request $request,QuizRepository $quizRepository ){
        for($i=1; $i<=count($quiz->getQuizQuestion()); $i++){
            $quizReponse[] = str_replace(' ','_',$request->request->get("reponse".$i));
        }
        //Gestion des erreurs et calcul de la note
        $note = 0;
        $erreur = 0;
        $tabQuestionsIndice=[];
        $tabErreurs = [];
        $tabCorrections = [];
        $quizreponseCorrection = $quiz->getQuizReponse();
        for($i=0; $i<count($quiz->getQuizQuestion()); $i++){
            if ( $quizReponse[$i] == $quizreponseCorrection{$i} ){
                $note = $note+1;
            }
            if ( $quizReponse[$i] != $quizreponseCorrection{$i} ){
                $erreur = $erreur+1;
                $tabErreurs[]=$quizReponse[$i];
                $tabQuestionsIndice[]=$i+1;
                $tabCorrections[]=$quizreponseCorrection{$i};
            }
        }
        // Création du quizz a refaire suivant les erreurs
        if($erreur!=0){
           $quizARefaire  = clone($quiz);
            $tabQuestions = $quiz->getQuizQuestion();
            $tabReponse = $quiz->getQuizReponse();
            for($i=0; $i<count($tabQuestionsIndice); $i++){
                $questionsARefaire[]= $tabQuestions[$tabQuestionsIndice[$i]-1];
                $reponsesARefaire[]= $tabReponse[$tabQuestionsIndice[$i]-1];
            }
            $quizARefaire->setQuizQuestion($questionsARefaire)
                         ->setQuizReponse($reponsesARefaire);
            $quizARefaire->updatedAt = new \DateTime();
            $titre = $quiz->getNomDeFichier();
            $verif = preg_match("#A refaire#i", "'.$titre.'");
            if($verif == false){
                $quizARefaire->setNomDeFichier($quiz->getNomDeFichier().' A refaire');
            }
        }
        $entityManager = $this->getDoctrine()->getManager();
        if (isset($quizARefaire)){
            $quizARefaire->setResultat($note/count($quiz->getQuizQuestion())*20);
            $quizARefaire->setQuizFait(true);
            $entityManager->persist($quizARefaire);
        } else {
            $quiz->setResultat($note/count($quiz->getQuizQuestion())*20);
            $quiz->setQuizFait(true);
            $entityManager->persist($quiz);
        }

        $note = $note/count($quiz->getQuizQuestion())*20;

        $titre = $quiz->getNomDeFichier();
        $verif = preg_match("#A refaire#i", "'.$titre.'");

        if($note == 20 and $verif == true){
            $titre = substr($titre, 0, -10);
            $PremierQuiz = $quizRepository->getQuizByTitre($titre);
            if (isset($PremierQuiz)){
                $PremierQuiz[0]->setResultat(20);
                $PremierQuiz[0]->setQuizFait(true);
                $entityManager->persist($PremierQuiz[0]);
            }
        }
        $entityManager->flush();

        return $this->render('quiz/correctionQuiz.html.twig', [
            'quiz' => $quiz,
            'note' => $note,
            'erreur' => $erreur,
            'quizQuestion'=> $quiz->getQuizQuestion(),
            'quizReponse' => $quizReponse,
            'quizCorrection' => $quizreponseCorrection,
            'tabQuestions'=> $tabQuestionsIndice,
            'tabErreurs' => $tabErreurs,
            'tabCorrections' => $tabCorrections
        ]);
    }


    /**
     * @Route("/{id}", name="quiz_show", methods={"GET"})
     */
    public function show(Quiz $quiz): Response
    {
        return $this->render('quiz/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="quiz_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Quiz $quiz): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('quiz_index', [
                'id' => $quiz->getId(),
            ]);
        }

        return $this->render('quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="quiz_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Quiz $quiz): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($quiz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('quiz_index');
    }
}

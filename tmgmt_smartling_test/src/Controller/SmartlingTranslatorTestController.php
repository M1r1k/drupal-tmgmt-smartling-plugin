<?php
/**
 * @file
 * Contains \Drupal\tmgmt_smartling_test\Controller\SmartlingTranslatorTestController.
 */

namespace Drupal\tmgmt_smartling_test\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mock services for Smartling translator.
 */
class SmartlingTranslatorTestController {


  /**
   * Helper to trigger mock response error.
   *
   * @param string $domain
   * @param string $reason
   * @param string $message
   * @param string $locationType
   * @param string $location
   */
  public function trigger_response_error($domain, $reason, $message, $locationType = NULL, $location = NULL) {

    $response = array(
      'error' => array(
        'errors' => array(
          'domain' => $domain,
          'reason' => $reason,
          'message' => $message,
        ),
        'code' => 400,
        'message' => $message,
      ),
    );

    if (!empty($locationType)) {
      $response['error']['errors']['locationType'] = $locationType;
    }
    if (!empty($location)) {
      $response['error']['errors']['location'] = $location;
    }

    return new JsonResponse($response);
  }

  /**
   * Page callback for getting the supported languages.
   */
  public function locales_list(Request $request) {
    $language_es = new \stdClass();
    $language_es->name = 'Spanish';
    $language_es->locale = 'es';
    $language_es->translated = 'Español';

    $language_nl = new \stdClass();
    $language_nl->name = 'Dutch';
    $language_nl->locale = 'nl';
    $language_nl->translated = 'Dutch';

    return JsonResponse::create([$language_es, $language_nl]);
  }

  public function get_status(Request $request) {
    $status = [
      "fileUri" => $request->get('fileUri'),
      "stringCount" => 100,
      "wordCount" => 100,
      "approvedStringCount" => 50,
      "completedStringCount" => 25,
      "lastUploaded" => date('Y-m-d\Thh:mm:ss'),
      "fileType" => 'xliff',
    ];

    return JsonResponse::create($status);
  }

  public function upload_file(Request $request) {
    $upload_status = [
      'overWritten' => 'true',
      'stringCount' => 100,
      'wordCount' => 20,
    ];

    return JsonResponse::create($upload_status);
  }

  public function download_file(Request $request) {
    $xliff_string = '<?xml version="1.0" encoding="UTF-8"?> <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="urn:oasis:names:tc:xliff:document:1.2 xliff-core-1.2-strict.xsd"> <file original="xliff-core-1.2-strict.xsd" source-language="en-EN" target-language="nl-NL" datatype="plaintext" date="2015-11-29T03:11:58Z"> <header> <phase-group> <phase tool-id="tmgmt" phase-name="extraction" process-name="extraction" job-id="16"/> </phase-group> <tool tool-id="tmgmt" tool-name="Drupal Translation Management Tools"/> </header> <body> <group id="16"> <note>Hendrerit Ille Luctus Veniam</note> <trans-unit id="16][title][0][value" resname="16][title][0][value"> <source xml:lang="en-EN">Hendrerit Ille Luctus Veniam</source> <target xml:lang="nl-NL"/> <note>Text value</note> </trans-unit> <trans-unit id="16][body][0][value" resname="16][body][0][value"> <source xml:lang="en-EN">Eum hendrerit laoreet odio sino ut velit. At ideo plaga quia. At defui hendrerit mauris mos probo suscipit tation utinam. Distineo iustum ulciscor. Aliquip enim ludus pagus probo refero tation vero virtus. Defui eu euismod gemino jus lobortis nutus occuro praemitto turpis. Euismod exerci ibidem iustum laoreet mauris meus nunc persto utinam. Damnum immitto pecus tincidunt voco. Capto erat nobis pecus pertineo si suscipere valde volutpat. Euismod facilisis plaga typicus vulpes. Ad damnum gemino genitus jus laoreet luptatum oppeto paratus turpis. Abluo ad olim tamen. Nutus praesent qui sed. Ad eu ideo inhibeo letalis nulla sit wisi. Commoveo nobis quibus. Haero in nunc oppeto proprius te. Ad eligo gemino meus modo quidem quis tamen wisi ymo. Abico lobortis natu nutus qui sit. Brevitas macto mos nostrud quibus refero utrum. Blandit ea ideo natu neo secundum tego. Commoveo huic jumentum pala quadrum refoveo venio virtus. Elit quidne tation te. Et lobortis metuo. Bene erat eum gravis huic letalis occuro os pneum suscipit. Acsi duis in laoreet mos pagus pecus ratis te. Abdo hos nobis populus quis sudo vindico zelus. Autem importunus iriure jus neo nibh oppeto refoveo secundum suscipere. Ille si singularis. Antehabeo causa incassum loquor secundum sed similis typicus. Ad elit os quae secundum. Ad appellatio consectetuer duis facilisi hos in luptatum nunc. Consectetuer dolor eros esca ibidem laoreet qui refoveo torqueo. Dolore luptatum os volutpat. Exerci imputo sudo utrum veniam. Et haero letalis lobortis lucidus luptatum nutus patria paulatim sudo. Abdo acsi eros oppeto quibus. Eu genitus immitto importunus nobis oppeto pagus praesent ulciscor. At dolor exerci importunus macto voco ymo. Conventio facilisi iaceo loquor nulla quidne. Comis exerci ibidem immitto iustum te usitas vindico volutpat wisi. Iusto neque pecus sino. Abico amet genitus importunus. Abbas accumsan ad ex nibh premo probo quae typicus venio. Eu jugis lobortis lucidus magna paratus quia quidem utinam venio. Dolus hendrerit iriure modo oppeto praesent tego vereor. Amet caecus duis hendrerit ille loquor odio uxor. Aliquip aptent dignissim eum interdico quia quis ratis roto. Minim pagus utrum. Abluo amet camur elit eu jugis lenis olim qui zelus. Abico eum facilisis modo nibh paulatim pecus sagaciter tamen. Abigo duis in nunc persto vindico ymo. Adipiscing quia quidne sino utinam vulputate. Abico comis eros importunus jumentum letalis nutus saluto vereor. Consectetuer elit exputo humo ille inhibeo meus nulla premo validus. Accumsan aliquip persto populus praesent tum. Feugiat iaceo illum pneum quidem similis singularis suscipit tego zelus. Caecus ex haero humo macto melior mos oppeto singularis utinam. Antehabeo appellatio consectetuer elit iaceo ibidem os pecus venio. Fere jumentum mauris pertineo praesent sit suscipit valetudo. Antehabeo importunus te virtus. Abigo ad blandit in refero saluto suscipit tego ulciscor vicis. Blandit genitus tego ullamcorper virtus. At commodo exputo ille iriure modo os rusticus torqueo ullamcorper. Amet bene esca molior pecus venio vereor virtus. Aliquip exputo facilisi loquor os suscipit voco ymo. Facilisis hendrerit in neque occuro sino sudo validus. </source> <target xml:lang="nl-NL"/> <note>Text</note> </trans-unit> <trans-unit id="16][body][0][summary" resname="16][body][0][summary"> <source xml:lang="en-EN">Eum hendrerit laoreet odio sino ut velit. At ideo plaga quia. At defui hendrerit mauris mos probo suscipit tation utinam. Distineo iustum ulciscor. Aliquip enim ludus pagus probo refero tation vero virtus. Defui eu euismod gemino jus lobortis nutus occuro praemitto turpis. Euismod exerci ibidem iustum laoreet mauris meus nunc persto utinam. Damnum immitto pecus tincidunt voco. Capto erat nobis pecus pertineo si suscipere valde volutpat. Euismod facilisis plaga typicus vulpes. Ad damnum gemino genitus jus laoreet luptatum oppeto paratus turpis. Abluo ad olim tamen. Nutus praesent qui sed. Ad eu ideo inhibeo letalis nulla sit wisi. Commoveo nobis quibus. Haero in nunc oppeto proprius te. Ad eligo gemino meus modo quidem quis tamen wisi ymo. Abico lobortis natu nutus qui sit. Brevitas macto mos nostrud quibus refero utrum. Blandit ea ideo natu neo secundum tego. Commoveo huic jumentum pala quadrum refoveo venio virtus. Elit quidne tation te. Et lobortis metuo. Bene erat eum gravis huic letalis occuro os pneum suscipit. Acsi duis in laoreet mos pagus pecus ratis te. Abdo hos nobis populus quis sudo vindico zelus. Autem importunus iriure jus neo nibh oppeto refoveo secundum suscipere. Ille si singularis. Antehabeo causa incassum loquor secundum sed similis typicus. Ad elit os quae secundum. Ad appellatio consectetuer duis facilisi hos in luptatum nunc. Consectetuer dolor eros esca ibidem laoreet qui refoveo torqueo. Dolore luptatum os volutpat. Exerci imputo sudo utrum veniam. Et haero letalis lobortis lucidus luptatum nutus patria paulatim sudo. Abdo acsi eros oppeto quibus. Eu genitus immitto importunus nobis oppeto pagus praesent ulciscor. At dolor exerci importunus macto voco ymo. Conventio facilisi iaceo loquor nulla quidne. Comis exerci ibidem immitto iustum te usitas vindico volutpat wisi. Iusto neque pecus sino. Abico amet genitus importunus. Abbas accumsan ad ex nibh premo probo quae typicus venio. Eu jugis lobortis lucidus magna paratus quia quidem utinam venio. Dolus hendrerit iriure modo oppeto praesent tego vereor. Amet caecus duis hendrerit ille loquor odio uxor. Aliquip aptent dignissim eum interdico quia quis ratis roto. Minim pagus utrum. Abluo amet camur elit eu jugis lenis olim qui zelus. Abico eum facilisis modo nibh paulatim pecus sagaciter tamen. Abigo duis in nunc persto vindico ymo. Adipiscing quia quidne sino utinam vulputate. Abico comis eros importunus jumentum letalis nutus saluto vereor. Consectetuer elit exputo humo ille inhibeo meus nulla premo validus. Accumsan aliquip persto populus praesent tum. Feugiat iaceo illum pneum quidem similis singularis suscipit tego zelus. Caecus ex haero humo macto melior mos oppeto singularis utinam. Antehabeo appellatio consectetuer elit iaceo ibidem os pecus venio. Fere jumentum mauris pertineo praesent sit suscipit valetudo. Antehabeo importunus te virtus. Abigo ad blandit in refero saluto suscipit tego ulciscor vicis. Blandit genitus tego ullamcorper virtus. At commodo exputo ille iriure modo os rusticus torqueo ullamcorper. Amet bene esca molior pecus venio vereor virtus. Aliquip exputo facilisi loquor os suscipit voco ymo. Facilisis hendrerit in neque occuro sino sudo validus. </source> <target xml:lang="nl-NL"/> <note>Summary</note> </trans-unit> <trans-unit id="16][field_image][0][alt" resname="16][field_image][0][alt"> <source xml:lang="en-EN">Consequat patria quidne. Acsi consequat diam ex facilisis melior.</source> <target xml:lang="nl-NL"/> <note>Alternative text</note> </trans-unit> <trans-unit id="16][field_image][0][title" resname="16][field_image][0][title"> <source xml:lang="en-EN">Causa plaga proprius. Aliquip caecus causa euismod ille jus laoreet oppeto torqueo verto.</source> <target xml:lang="nl-NL"/> <note>Title</note> </trans-unit> </group> </body> </file> </xliff>';
    return new Response($xliff_string);
  }

}
